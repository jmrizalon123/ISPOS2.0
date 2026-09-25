<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Models\PriceGroup;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PriceGroupService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCompanyCatalog(
            $user,
            PriceGroup::class,
            ['group_code', 'name', 'description'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($status) {
                $this->applyCatalogStatusFilter($q, $status);
                $q->orderByDesc('is_default')->orderBy('name');
            },
        );
    }

    public function create(array $data, ?User $actor = null): PriceGroup
    {
        return DB::transaction(function () use ($data, $actor) {
            $this->stampActor($data, $actor);
            $this->normalizeDefault($data);

            $priceGroup = PriceGroup::create($data);
            $this->logCatalogCreate('price_groups', PriceGroup::class, $priceGroup);

            return $priceGroup;
        });
    }

    public function update(PriceGroup $priceGroup, array $data, ?User $actor = null): PriceGroup
    {
        return DB::transaction(function () use ($priceGroup, $data, $actor) {
            if ($actor) {
                $data['updated_by'] = $actor->id;
            }

            $this->normalizeDefault($data, $priceGroup);

            $old = $priceGroup->toArray();
            $priceGroup->update($data);
            $this->logCatalogUpdate('price_groups', PriceGroup::class, $priceGroup, $old);

            return $priceGroup->fresh();
        });
    }

    public function delete(PriceGroup $priceGroup): void
    {
        $this->logCatalogDelete('price_groups', PriceGroup::class, $priceGroup);
        $priceGroup->delete();
    }

    /** @param  array<string, mixed>  $data */
    protected function normalizeDefault(array &$data, ?PriceGroup $except = null): void
    {
        if (empty($data['is_default'])) {
            return;
        }

        $query = PriceGroup::query()->where('company_id', $data['company_id'] ?? $except?->company_id);

        if ($except) {
            $query->whereKeyNot($except->id);
        }

        $query->update(['is_default' => false]);
    }
}
