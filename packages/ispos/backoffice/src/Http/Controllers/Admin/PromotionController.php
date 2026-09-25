<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Crm\Services\PromotionService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StorePromotionRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdatePromotionRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(protected PromotionService $promotionService)
    {
        $this->authorizeResource(Promotion::class, 'promotion');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/Crm/Promotions/Index', [
            'promotions' => $this->promotionService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $ctx['companyId'],
                $ctx['status'],
            ),
            'companies' => $this->companyOptions($request),
            'filters' => $ctx['filters'],
        ]);
    }

    public function create(Request $request): Response
    {
        $companyId = $request->user()?->company_id ?: $this->companyOptions($request)[0]['id'] ?? null;

        return Inertia::render('Admin/Crm/Promotions/Form', [
            'promotion' => null,
            'companies' => $this->companyOptions($request),
            'products' => $this->promotionProductOptions($companyId),
            'categories' => $companyId
                ? Category::query()->where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'category_code'])
                : [],
        ]);
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $productIds = $data['product_ids'] ?? [];
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['product_ids'], $data['category_ids']);

        $this->promotionService->create($data, $productIds, $categoryIds, $request->user());

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion created.');
    }

    public function edit(Request $request, Promotion $promotion): Response
    {
        $promotion->load(['company:id,name', 'products:id,name,sku', 'categories:id,name,category_code']);

        return Inertia::render('Admin/Crm/Promotions/Form', [
            'promotion' => $promotion,
            'companies' => $this->companyOptions($request),
            'products' => $this->promotionProductOptions($promotion->company_id),
            'categories' => Category::query()
                ->where('company_id', $promotion->company_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'category_code']),
        ]);
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $data = $request->validated();
        $productIds = $data['product_ids'] ?? [];
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['product_ids'], $data['category_ids']);

        $this->promotionService->update($promotion, $data, $productIds, $categoryIds, $request->user());

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion updated.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $this->promotionService->delete($promotion);

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion deleted.');
    }

    public function activate(Request $request, Promotion $promotion): RedirectResponse
    {
        $this->authorize('activate', $promotion);
        $this->promotionService->activate($promotion, $request->user());

        return back()->with('success', 'Promotion activated.');
    }

    public function cancel(Request $request, Promotion $promotion): RedirectResponse
    {
        $this->authorize('cancel', $promotion);
        $this->promotionService->cancel($promotion, $request->user());

        return back()->with('success', 'Promotion cancelled.');
    }

    /**
     * @return list<array{id: string, name: string, sku: string, cost: string|null, qty: string|null, image: string|null, supplier: string|null, barcode: string|null, barcodes: list<string>}>
     */
    protected function promotionProductOptions(?string $companyId): array
    {
        if (! $companyId) {
            return [];
        }

        return Product::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->with([
                'brand:id,name',
                'barcodes' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('barcode'),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'cost', 'qty', 'image', 'brand_id'])
            ->map(function (Product $product) {
                $barcodes = $product->barcodes->pluck('barcode')->filter()->values()->all();
                $primaryBarcode = $product->barcodes->firstWhere('is_primary', true)?->barcode
                    ?? $barcodes[0]
                    ?? null;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'cost' => $product->cost,
                    'qty' => $product->qty,
                    'image' => $product->image,
                    'supplier' => $product->brand?->name,
                    'barcode' => $primaryBarcode,
                    'barcodes' => $barcodes,
                ];
            })
            ->all();
    }
}
