<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Catalog\Services\ProductService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreBulkProductsRequest;
use Ispos\Backoffice\Http\Requests\Admin\StoreProductRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceGroup;
use App\Models\Product;
use App\Models\SalesPlan;
use App\Models\Tax;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(protected ProductService $productService)
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/Products/Index', [
            'products' => $this->productService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $ctx['companyId'],
                [
                    'category_id' => $request->string('category_id')->toString() ?: null,
                    'brand_id' => $request->string('brand_id')->toString() ?: null,
                    'store_id' => $ctx['storeId'],
                ],
            ),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'categories' => $this->catalogFilterOptions(Category::class, 'category_code', $ctx['companyId']),
            'brands' => $this->catalogFilterOptions(Brand::class, 'brand_code', $ctx['companyId']),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'company_id' => $request->string('company_id')->toString(),
                'store_id' => $ctx['storeId'] ?? '',
                'category_id' => $request->string('category_id')->toString(),
                'brand_id' => $request->string('brand_id')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $companyId = $this->resolveCompanyId($request);

        return Inertia::render('Admin/Products/Form', [
            'product' => null,
            'companies' => $this->companyOptions($request),
            ...$this->catalogOptions($companyId),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->productService->create(
            $request->validated(),
            $request->user(),
            $request->file('image_uploads', []) ?? [],
        );

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function bulkCreate(Request $request): Response
    {
        $this->authorize('create', Product::class);

        $companyId = $this->resolveCompanyId($request);

        return Inertia::render('Admin/Products/BulkCreate', [
            'companies' => $this->companyOptions($request),
            ...$this->bulkCatalogOptions($companyId),
        ]);
    }

    public function bulkStore(StoreBulkProductsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        foreach ($data['items'] as $index => &$item) {
            $item['image_uploads'] = $request->file("items.{$index}.image_uploads", []) ?? [];
        }
        unset($item);

        $created = $this->productService->createMany($data, $request->user());
        $count = $created->count();

        return redirect()->route('admin.products.index')->with(
            'success',
            $count === 1 ? 'Product created.' : "{$count} products created.",
        );
    }

    public function edit(Request $request, Product $product): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => $product->load([
                'company:id,name,company_code',
                'salesPlan:id,name,plan_code',
                'category:id,name,category_code',
                'brand:id,name,brand_code',
                'unit:id,name,symbol',
                'tax:id,name,rate',
                'variants',
                'barcodes',
                'prices',
                'modifierGroups.options',
                'components.componentProduct:id,name,sku,product_type',
                'components.unit:id,name,symbol',
                'ingredients.ingredientProduct:id,name,sku,product_type',
                'ingredients.unit:id,name,symbol',
                'images',
            ]),
            'companies' => $this->companyOptions($request),
            ...$this->catalogOptions($product->company_id),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->update(
            $product,
            $request->validated(),
            $request->user(),
            $request->file('image_uploads', []) ?? [],
        );

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->delete($product);

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    protected function bulkCatalogOptions(?string $companyId): array
    {
        return [
            'categories' => Category::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'category_code', 'company_id']),
            'brands' => Brand::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'brand_code', 'company_id']),
            'units' => Unit::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'symbol', 'company_id']),
            'taxes' => Tax::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'rate', 'company_id']),
            'ingredientProducts' => Product::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('product_type', 'ingredient')
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'company_id']),
            'componentProducts' => Product::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereIn('product_type', ['retail', 'menu_item'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'company_id']),
            'salesPlans' => $this->salesPlanOptions($companyId),
        ];
    }

    protected function catalogOptions(?string $companyId): array
    {
        return [
            'categories' => Category::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'category_code', 'company_id']),
            'brands' => Brand::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'brand_code', 'company_id']),
            'units' => Unit::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'symbol', 'company_id']),
            'taxes' => Tax::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'rate', 'company_id']),
            'priceGroups' => PriceGroup::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'group_code', 'is_default', 'company_id']),
            'ingredientProducts' => Product::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('product_type', 'ingredient')
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'company_id', 'product_type', 'sales_plan_id']),
            'componentProducts' => Product::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereIn('product_type', ['retail', 'menu_item'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'company_id', 'product_type', 'sales_plan_id']),
            'salesPlans' => $this->salesPlanOptions($companyId),
        ];
    }

    protected function salesPlanOptions(?string $companyId)
    {
        return SalesPlan::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name', 'plan_code', 'company_id', 'status']);
    }

    /** @param  class-string<Category|Brand>  $modelClass */
    protected function catalogFilterOptions(string $modelClass, string $codeColumn, ?string $companyId)
    {
        return $modelClass::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name', $codeColumn, 'company_id']);
    }
}
