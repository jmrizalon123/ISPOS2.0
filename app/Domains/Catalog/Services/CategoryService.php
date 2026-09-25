<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCompanyCatalog(
            $user,
            Category::class,
            ['category_code', 'name', 'description'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($status) {
                $this->applyCatalogStatusFilter($q, $status);
                $q->with('parent:id,name,category_code')->orderBy('sort_order')->orderBy('name');
            },
        );
    }

    public function create(array $data, ?User $actor = null): Category
    {
        $this->stampActor($data, $actor);
        $category = Category::create($data);
        $this->logCatalogCreate('categories', Category::class, $category);

        return $category;
    }

    public function update(Category $category, array $data, ?User $actor = null): Category
    {
        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        $old = $category->toArray();
        $category->update($data);
        $this->logCatalogUpdate('categories', Category::class, $category, $old);

        return $category->fresh(['parent', 'company']);
    }

    public function delete(Category $category): void
    {
        $this->logCatalogDelete('categories', Category::class, $category);
        $category->delete();
    }
}
