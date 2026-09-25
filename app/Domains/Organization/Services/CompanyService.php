<?php

namespace App\Domains\Organization\Services;

use App\Models\Company;
use App\Models\SalesPlan;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CompanyService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return Company::query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->when($search, fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('company_code', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, ?User $actor = null): Company
    {
        if ($actor) {
            $data['created_by'] = $actor->id;
            $data['updated_by'] = $actor->id;
        }

        $company = Company::create($data);
        $this->ensureDefaultSalesPlan($company, $actor);

        $this->auditLogger->log('create', 'companies', Company::class, $company->id, null, $company->toArray());

        return $company;
    }

    public function update(Company $company, array $data, ?User $actor = null): Company
    {
        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        $old = $company->toArray();
        $company->update($data);

        $this->auditLogger->log('update', 'companies', Company::class, $company->id, $old, $company->fresh()->toArray());

        return $company->fresh();
    }

    public function delete(Company $company): void
    {
        $old = $company->toArray();

        $company->users()->update(['company_id' => null]);
        $company->delete();

        $this->auditLogger->log('delete', 'companies', Company::class, $company->id, $old, null);
    }

    protected function ensureDefaultSalesPlan(Company $company, ?User $actor = null): void
    {
        SalesPlan::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'plan_code' => 'DEFAULT',
            ],
            [
                'name' => 'Default',
                'description' => 'Starter sales plan. Split retail and restaurant catalogs by creating additional plans.',
                'status' => 'active',
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ],
        );
    }
}
