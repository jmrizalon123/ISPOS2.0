<?php

namespace App\Domains\Purchasing\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCompanyCatalog(
            $user,
            Supplier::class,
            ['supplier_code', 'name', 'contact_name', 'email'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($status) {
                $this->applyCatalogStatusFilter($q, $status);
                $q->orderBy('name');
            },
        );
    }

    public function create(array $data, ?User $actor = null): Supplier
    {
        $this->stampActor($data, $actor);
        $supplier = Supplier::create($data);
        $this->logCatalogCreate('suppliers', Supplier::class, $supplier);

        return $supplier;
    }

    public function update(Supplier $supplier, array $data, ?User $actor = null): Supplier
    {
        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        $old = $supplier->toArray();
        $supplier->update($data);
        $this->logCatalogUpdate('suppliers', Supplier::class, $supplier, $old);

        return $supplier->fresh();
    }

    public function delete(Supplier $supplier): void
    {
        $this->logCatalogDelete('suppliers', Supplier::class, $supplier);
        $supplier->delete();
    }
}
