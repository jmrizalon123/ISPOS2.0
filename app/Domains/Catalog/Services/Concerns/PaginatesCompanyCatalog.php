<?php

namespace App\Domains\Catalog\Services\Concerns;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait PaginatesCompanyCatalog
{
    /** @param  class-string<Model>  $modelClass
     * @param  list<string>  $searchColumns
     */
    protected function paginateCompanyCatalog(
        User $user,
        string $modelClass,
        array $searchColumns,
        ?string $search = null,
        ?string $companyId = null,
        int $perPage = 15,
        ?callable $configure = null,
    ): LengthAwarePaginator {
        /** @var Builder $query */
        $query = $modelClass::query()
            ->with(['company:id,name,company_code,display_name', 'creator:id,name', 'updater:id,name'])
            ->when(! $user->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $user->company_id))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($search, function ($q) use ($search, $searchColumns) {
                $q->where(function ($inner) use ($search, $searchColumns) {
                    foreach ($searchColumns as $column) {
                        $inner->orWhere($column, 'like', "%{$search}%");
                    }
                });
            });

        if ($configure) {
            $configure($query);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    protected function applyCatalogStatusFilter(Builder $query, ?string $status): void
    {
        $query->when($status, fn ($q) => $q->where('status', $status));
    }
}
