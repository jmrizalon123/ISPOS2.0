<?php

namespace App\Domains\Identity\Services;

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class BackofficeContextService
{
    public const SESSION_KEY = 'backoffice_context';

    public const ESTABLISHED_SESSION_KEY = 'backoffice_context_established';

    /** @return array{scope: string|null, company_id: string|null, store_id: string|null} */
    public function get(): array
    {
        $ctx = Session::get(self::SESSION_KEY, []);

        return [
            'scope' => $ctx['scope'] ?? null,
            'company_id' => $ctx['company_id'] ?? null,
            'store_id' => $ctx['store_id'] ?? null,
        ];
    }

    public function clear(): void
    {
        Session::forget([
            self::SESSION_KEY,
            self::ESTABLISHED_SESSION_KEY,
        ]);
    }

    public function wasEstablished(): bool
    {
        return (bool) Session::get(self::ESTABLISHED_SESSION_KEY, false);
    }

    protected function markEstablished(): void
    {
        Session::put(self::ESTABLISHED_SESSION_KEY, true);
    }

    public function isSet(): bool
    {
        $ctx = $this->get();

        if ($ctx['scope'] === 'company') {
            return (bool) $ctx['company_id'];
        }

        if ($ctx['scope'] === 'store') {
            return (bool) $ctx['store_id'];
        }

        return false;
    }

    public function isStoreScope(): bool
    {
        return $this->get()['scope'] === 'store';
    }

    public function isCompanyScope(): bool
    {
        return $this->get()['scope'] === 'company';
    }

    public function getStoreId(): ?string
    {
        return $this->isStoreScope() ? $this->get()['store_id'] : null;
    }

    public function getCompanyId(): ?string
    {
        return $this->get()['company_id'];
    }

    /** Session store filter when scope is store; null in company scope. */
    public function effectiveStoreId(User $user): ?string
    {
        if (! $this->isStoreScope()) {
            return null;
        }

        $storeId = $this->getStoreId();
        if (! $storeId) {
            return null;
        }

        $store = Store::query()->find($storeId);
        if (! $store || ! $user->canAccessStore($store)) {
            return null;
        }

        return $store->id;
    }

    public function canSelectCompanyScope(User $user): bool
    {
        return $user->hasGlobalOrganizationAccess()
            || $user->hasRole('Company Admin')
            || $user->isHeadOfficeBased();
    }

    /** @return list<array{id: string, store_name: string, store_code: string}> */
    public function accessibleStores(User $user): array
    {
        $query = Store::query()->where('status', 'active')->orderBy('store_name');

        if (! $user->hasGlobalOrganizationAccess()) {
            if ($user->hasRole('Company Admin') || $user->isHeadOfficeBased()) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->whereIn('id', $user->stores()->pluck('stores.id'));
            }
        }

        return $query->get(['id', 'store_name', 'store_code'])->map(fn (Store $store) => [
            'id' => $store->id,
            'store_name' => $store->store_name,
            'store_code' => $store->store_code,
        ])->all();
    }

    /** @return array{can_select_company: bool, company: array{id: string, name: string, company_code: string, display_name: string|null}|null, stores: list<array{id: string, store_name: string, store_code: string}>} */
    public function selectionOptions(User $user): array
    {
        $company = null;
        if ($user->company_id) {
            $companyModel = Company::query()->find($user->company_id);
            if ($companyModel) {
                $company = [
                    'id' => $companyModel->id,
                    'name' => $companyModel->name,
                    'company_code' => $companyModel->company_code,
                    'display_name' => $companyModel->display_name,
                ];
            }
        }

        return [
            'can_select_company' => $this->canSelectCompanyScope($user) && $company !== null,
            'company' => $company,
            'stores' => $this->accessibleStores($user),
        ];
    }

    public function selectionChoiceCount(User $user): int
    {
        $options = $this->selectionOptions($user);

        return count($options['stores']) + ($options['can_select_company'] ? 1 : 0);
    }

    public function attemptAutoResolve(User $user): bool
    {
        if ($this->isSet()) {
            return true;
        }

        $options = $this->selectionOptions($user);
        $storeCount = count($options['stores']);
        $canCompany = $options['can_select_company'];
        $choiceCount = $storeCount + ($canCompany ? 1 : 0);

        if ($choiceCount === 0) {
            return false;
        }

        if ($choiceCount === 1) {
            if ($canCompany && $storeCount === 0) {
                $this->setCompanyScope($user);
            } else {
                $this->setStoreScope($user, $options['stores'][0]['id']);
            }

            return true;
        }

        return false;
    }

    public function setCompanyScope(User $user): void
    {
        if (! $this->canSelectCompanyScope($user)) {
            abort(403);
        }

        $companyId = $user->company_id;
        if (! $companyId) {
            throw ValidationException::withMessages([
                'scope' => 'No company is assigned to this account.',
            ]);
        }

        if (! $user->canAccessCompany($companyId)) {
            abort(403);
        }

        Session::put(self::SESSION_KEY, [
            'scope' => 'company',
            'company_id' => $companyId,
            'store_id' => null,
        ]);

        $this->markEstablished();
    }

    public function setStoreScope(User $user, string $storeId): void
    {
        $store = Store::query()->findOrFail($storeId);

        if (! $user->canAccessStore($store)) {
            abort(403);
        }

        if ($store->status !== 'active') {
            throw ValidationException::withMessages(['store_id' => 'Store is not active.']);
        }

        Session::put(self::SESSION_KEY, [
            'scope' => 'store',
            'company_id' => $store->company_id,
            'store_id' => $store->id,
        ]);

        $this->markEstablished();
    }

    /** @return array{scope: string, label: string, company_id: string|null, store_id: string|null, store_name: string|null, store_code: string|null}|null */
    public function sharePayload(User $user): ?array
    {
        if (! $this->isSet()) {
            return null;
        }

        $ctx = $this->get();

        if ($ctx['scope'] === 'company') {
            $company = Company::query()->find($ctx['company_id']);

            return [
                'scope' => 'company',
                'label' => $company?->display_name ?: $company?->name ?? 'Company',
                'company_id' => $ctx['company_id'],
                'store_id' => null,
                'store_name' => null,
                'store_code' => null,
            ];
        }

        $store = Store::query()->find($ctx['store_id']);

        return [
            'scope' => 'store',
            'label' => $store?->store_name ?? 'Store',
            'company_id' => $ctx['company_id'],
            'store_id' => $ctx['store_id'],
            'store_name' => $store?->store_name,
            'store_code' => $store?->store_code,
        ];
    }

    public function constrainsToStore(User $user): bool
    {
        return $this->effectiveStoreId($user) !== null;
    }

    public function allowsStore(User $user, Store|string $store): bool
    {
        $storeId = $store instanceof Store ? $store->id : $store;
        $contextStoreId = $this->effectiveStoreId($user);

        if ($contextStoreId) {
            return $contextStoreId === $storeId;
        }

        return true;
    }

    public function canSwitchContext(User $user): bool
    {
        return $this->selectionChoiceCount($user) > 1;
    }

    /** @param  Builder<User>  $query */
    public function applyUserQueryScope(Builder $query, User $actor): Builder
    {
        $contextStoreId = $this->effectiveStoreId($actor);

        if ($contextStoreId) {
            return $query->whereHas(
                'stores',
                fn (Builder $storeQuery) => $storeQuery->where('stores.id', $contextStoreId),
            );
        }

        if (! $actor->hasGlobalOrganizationAccess()) {
            return $query->where('company_id', $actor->company_id);
        }

        return $query;
    }

    public function userInScope(User $actor, User $target): bool
    {
        $contextStoreId = $this->effectiveStoreId($actor);

        if ($contextStoreId) {
            return $target->stores()->where('stores.id', $contextStoreId)->exists();
        }

        return true;
    }

    /** @param  Builder<Store>  $query */
    public function applyStoreQueryScope(Builder $query, User $actor): Builder
    {
        $contextStoreId = $this->effectiveStoreId($actor);

        if ($contextStoreId) {
            return $query->whereKey($contextStoreId);
        }

        if (! $actor->hasGlobalOrganizationAccess()) {
            if ($actor->hasRole('Company Admin') || $actor->isHeadOfficeBased()) {
                return $query->where('company_id', $actor->company_id);
            }

            return $query->whereIn('id', $actor->stores()->pluck('stores.id'));
        }

        return $query;
    }

    public function validateCurrentContext(User $user): bool
    {
        if (! $this->isSet()) {
            return false;
        }

        if ($this->isCompanyScope()) {
            $companyId = $this->getCompanyId();

            return $companyId && $user->canAccessCompany($companyId) && $this->canSelectCompanyScope($user);
        }

        $storeId = $this->getStoreId();
        if (! $storeId) {
            return false;
        }

        $store = Store::query()->find($storeId);

        return $store && $user->canAccessStore($store);
    }
}
