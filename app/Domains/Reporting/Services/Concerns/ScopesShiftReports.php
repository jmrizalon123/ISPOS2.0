<?php

namespace App\Domains\Reporting\Services\Concerns;

use App\Domains\Identity\Services\BackofficeContextService;
use App\Models\PosShift;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopesShiftReports
{
    /** @return Builder<PosShift> */
    protected function scopedShiftsQuery(User $user): Builder
    {
        $contextStoreId = app(BackofficeContextService::class)->effectiveStoreId($user);

        if ($contextStoreId) {
            return PosShift::query()->where('store_id', $contextStoreId);
        }

        $query = PosShift::query();

        if ($user->hasGlobalOrganizationAccess()) {
            return $query;
        }

        if ($user->hasRole('Company Admin') || $user->isHeadOfficeBased()) {
            return $query->where('company_id', $user->company_id);
        }

        $storeIds = $user->stores()->pluck('stores.id');

        return $query->whereIn('store_id', $storeIds);
    }

    /**
     * @param  array{company_id?: string|null, store_id?: string|null, date_from?: string|null, date_to?: string|null, status?: string|null}  $filters
     * @return Builder<PosShift>
     */
    protected function applyShiftFilters(Builder $query, User $user, array $filters): Builder
    {
        if (! empty($filters['company_id']) && $user->hasGlobalOrganizationAccess()) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('opened_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('opened_at', '<=', $filters['date_to']);
        }

        $status = $filters['status'] ?? null;
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query;
    }

    public function canViewShift(User $user, PosShift $shift): bool
    {
        return $this->scopedShiftsQuery($user)
            ->whereKey($shift->id)
            ->exists();
    }
}
