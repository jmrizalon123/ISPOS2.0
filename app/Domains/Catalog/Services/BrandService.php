<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrandService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCompanyCatalog(
            $user,
            Brand::class,
            ['brand_code', 'name'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($status) {
                $this->applyCatalogStatusFilter($q, $status);
                $q->orderBy('name');
            },
        );
    }

    public function create(array $data, ?User $actor = null): Brand
    {
        $this->stampActor($data, $actor);
        $brand = Brand::create($data);
        $this->logCatalogCreate('brands', Brand::class, $brand);

        return $brand;
    }

    public function update(Brand $brand, array $data, ?User $actor = null): Brand
    {
        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        $old = $brand->toArray();
        $brand->update($data);
        $this->logCatalogUpdate('brands', Brand::class, $brand, $old);

        return $brand->fresh();
    }

    public function delete(Brand $brand): void
    {
        $this->logCatalogDelete('brands', Brand::class, $brand);
        $brand->delete();
    }
}
