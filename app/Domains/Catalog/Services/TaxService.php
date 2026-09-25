<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaxService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCompanyCatalog(
            $user,
            Tax::class,
            ['tax_code', 'name'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($status) {
                $this->applyCatalogStatusFilter($q, $status);
                $q->orderBy('name');
            },
        );
    }

    public function create(array $data, ?User $actor = null): Tax
    {
        $this->stampActor($data, $actor);
        $tax = Tax::create($data);
        $this->logCatalogCreate('taxes', Tax::class, $tax);

        return $tax;
    }

    public function update(Tax $tax, array $data, ?User $actor = null): Tax
    {
        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        $old = $tax->toArray();
        $tax->update($data);
        $this->logCatalogUpdate('taxes', Tax::class, $tax, $old);

        return $tax->fresh();
    }

    public function delete(Tax $tax): void
    {
        $this->logCatalogDelete('taxes', Tax::class, $tax);
        $tax->delete();
    }
}
