<?php

namespace App\Domains\Accounting\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ChartOfAccountService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCompanyCatalog(
            $user,
            ChartOfAccount::class,
            ['account_code', 'account_name'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($status) {
                $this->applyCatalogStatusFilter($q, $status);
                $q->orderBy('account_code');
            },
        );
    }

    public function create(array $data, ?User $actor = null): ChartOfAccount
    {
        $data['normal_balance'] = ChartOfAccount::normalBalanceForType($data['account_type']);
        $this->stampActor($data, $actor);

        $account = ChartOfAccount::create($data);
        $this->logCatalogCreate('chart_of_accounts', ChartOfAccount::class, $account);

        return $account;
    }

    public function update(ChartOfAccount $account, array $data, ?User $actor = null): ChartOfAccount
    {
        if ($account->is_system && isset($data['account_code'])) {
            unset($data['account_code']);
        }

        if (isset($data['account_type'])) {
            $data['normal_balance'] = ChartOfAccount::normalBalanceForType($data['account_type']);
        }

        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        $old = $account->toArray();
        $account->update($data);
        $this->logCatalogUpdate('chart_of_accounts', ChartOfAccount::class, $account, $old);

        return $account->fresh();
    }

    public function delete(ChartOfAccount $account): void
    {
        if ($account->is_system) {
            throw ValidationException::withMessages(['account' => 'System accounts cannot be deleted.']);
        }

        if ($account->journalLines()->exists()) {
            throw ValidationException::withMessages(['account' => 'Account has journal activity and cannot be deleted.']);
        }

        $this->logCatalogDelete('chart_of_accounts', ChartOfAccount::class, $account);
        $account->delete();
    }
}
