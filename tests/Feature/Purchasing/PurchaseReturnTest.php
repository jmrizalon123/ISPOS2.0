<?php

namespace Tests\Feature\Purchasing;

use App\Domains\Purchasing\Services\PurchaseReturnService;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreProductInventory;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReturnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_post_return_deducts_stock_and_creates_movement(): void
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'track_inventory' => true,
            'status' => 'active',
        ]);

        StoreProductInventory::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'qty' => 50,
        ]);

        $user = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $user->assignRole('Purchasing Officer');

        $returnService = app(PurchaseReturnService::class);

        $return = $returnService->create($user, $store, $supplier, [
            'reason' => 'Expired stock',
        ], [
            ['product_id' => $product->id, 'qty' => 5, 'unit_cost' => 10],
        ]);

        $returnService->post($return, $user);

        $inventory = StoreProductInventory::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertEquals(45, (float) $inventory->qty);
        $this->assertSame(1, StockMovement::query()->where('movement_type', 'purchase_return')->count());
        $this->assertSame('posted', $return->fresh()->status);
    }
}
