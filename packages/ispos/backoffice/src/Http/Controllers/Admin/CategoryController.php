<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Catalog\Services\CategoryService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreCategoryRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(protected CategoryService $categoryService)
    {
        $this->authorizeResource(Category::class, 'category');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $this->categoryService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $ctx['companyId'],
                $ctx['status'],
            ),
            'companies' => $this->companyOptions($request),
            'stores' => $this->storeOptions($request),
            'filters' => $ctx['filters'],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => null,
            'companies' => $this->companyOptions($request),
            'parents' => $this->parentOptions($request),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->categoryService->create($request->validated(), $request->user());

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Request $request, Category $category): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => $category->load(['parent:id,name,category_code', 'company:id,name']),
            'companies' => $this->companyOptions($request),
            'parents' => $this->parentOptions($request, $category->company_id, $category->id),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categoryService->update($category, $request->validated(), $request->user());

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categoryService->delete($category);

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    protected function parentOptions(Request $request, ?string $companyId = null, ?string $exceptId = null)
    {
        $companyId = $companyId ?: $this->resolveCompanyId($request);

        return Category::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->orderBy('name')
            ->get(['id', 'name', 'category_code', 'company_id']);
    }
}
