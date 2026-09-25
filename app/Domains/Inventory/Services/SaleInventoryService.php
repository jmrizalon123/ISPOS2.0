<?php

namespace App\Domains\Inventory\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SaleLineComponent;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaleInventoryService
{
    public function __construct(protected StockMovementService $stockMovement) {}

    public function deductForSale(Sale $sale, User $user): void
    {
        $sale->load(['lines.product.ingredients.ingredientProduct', 'lines.components']);

        DB::transaction(function () use ($sale, $user) {
            foreach ($sale->lines as $line) {
                $this->deductForLine($sale, $line, $user);
            }
        });
    }

    public function reverseForSale(Sale $sale, User $user, string $movementType = 'void', string $reason = 'Sale voided'): void
    {
        $movements = StockMovement::query()
            ->where('sale_id', $sale->id)
            ->whereNull('reversal_of_id')
            ->whereNotIn('movement_type', ['void', 'refund'])
            ->get();

        DB::transaction(function () use ($movements, $sale, $user, $movementType, $reason) {
            foreach ($movements as $movement) {
                $alreadyReversed = StockMovement::query()
                    ->where('reversal_of_id', $movement->id)
                    ->exists();

                if ($alreadyReversed) {
                    continue;
                }

                $this->stockMovement->record(
                    $movement->company_id,
                    $movement->store_id,
                    $movement->product_id,
                    $movementType,
                    bcmul((string) $movement->quantity_delta, '-1', 4),
                    $user,
                    [
                        'sale_id' => $sale->id,
                        'sale_line_id' => $movement->sale_line_id,
                        'reversal_of_id' => $movement->id,
                        'reason' => $reason,
                    ],
                );
            }
        });
    }

    protected function deductForLine(Sale $sale, SaleLine $line, User $user): void
    {
        $product = $line->product;
        if (! $product) {
            return;
        }

        $components = $line->components->filter(fn (SaleLineComponent $c) => $c->included);

        if ($components->isNotEmpty()) {
            foreach ($components as $component) {
                $componentProduct = Product::query()->find($component->component_product_id);
                if (! $componentProduct) {
                    continue;
                }

                $componentQty = bcmul((string) $component->quantity, (string) $line->qty, 4);
                $this->resolveProductDeduction(
                    $sale,
                    $line,
                    $componentProduct,
                    (float) $componentQty,
                    $user,
                    'component_consumption',
                );
            }

            return;
        }

        $this->resolveProductDeduction($sale, $line, $product, (float) $line->qty, $user, 'sale');
    }

    protected function resolveProductDeduction(
        Sale $sale,
        SaleLine $line,
        Product $product,
        float $qty,
        User $user,
        string $defaultType,
    ): void {
        if ($product->track_inventory) {
            $this->recordDeduction(
                $sale,
                $line,
                $product,
                $qty,
                $user,
                $defaultType,
            );

            return;
        }

        if ($product->product_type === 'menu_item') {
            $product->loadMissing(['ingredients.ingredientProduct']);

            foreach ($product->ingredients as $ingredient) {
                if ($ingredient->is_optional) {
                    continue;
                }

                $ingredientProduct = $ingredient->ingredientProduct;
                if (! $ingredientProduct || ! $ingredientProduct->track_inventory) {
                    continue;
                }

                $deductQty = (float) bcmul((string) $ingredient->quantity, (string) $qty, 4);
                $this->recordDeduction(
                    $sale,
                    $line,
                    $ingredientProduct,
                    $deductQty,
                    $user,
                    'recipe_consumption',
                );
            }
        }
    }

    protected function recordDeduction(
        Sale $sale,
        SaleLine $line,
        Product $product,
        float $qty,
        User $user,
        string $movementType,
    ): void {
        if ($qty <= 0) {
            return;
        }

        $this->stockMovement->record(
            $sale->company_id,
            $sale->store_id,
            $product->id,
            $movementType,
            bcmul((string) $qty, '-1', 4),
            $user,
            [
                'sale_id' => $sale->id,
                'sale_line_id' => $line->id,
            ],
        );
    }
}
