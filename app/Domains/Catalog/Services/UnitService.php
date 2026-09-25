<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UnitService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCompanyCatalog(
            $user,
            Unit::class,
            ['unit_code', 'name', 'symbol'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($status) {
                $this->applyCatalogStatusFilter($q, $status);
                $q->orderBy('name');
            },
        );
    }

    public function create(array $data, ?User $actor = null): Unit
    {
        $this->stampActor($data, $actor);
        $unit = Unit::create($data);
        $this->logCatalogCreate('units', Unit::class, $unit);

        return $unit;
    }

    public function update(Unit $unit, array $data, ?User $actor = null): Unit
    {
        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        $old = $unit->toArray();
        $unit->update($data);
        $this->logCatalogUpdate('units', Unit::class, $unit, $old);

        return $unit->fresh();
    }

    public function delete(Unit $unit): void
    {
        $this->logCatalogDelete('units', Unit::class, $unit);
        $unit->delete();
    }
}
