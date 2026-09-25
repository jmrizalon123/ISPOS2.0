<?php

namespace App\Domains\Crm\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MembershipPlanService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCompanyCatalog(
            $user,
            MembershipPlan::class,
            ['plan_code', 'name'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($status) {
                $this->applyCatalogStatusFilter($q, $status);
                $q->with('priceGroup:id,name,group_code')->orderBy('name');
            },
        );
    }

    public function create(array $data, ?User $actor = null): MembershipPlan
    {
        $this->stampActor($data, $actor);
        $plan = MembershipPlan::create($data);
        $this->logCatalogCreate('membership_plans', MembershipPlan::class, $plan);

        return $plan;
    }

    public function update(MembershipPlan $plan, array $data, ?User $actor = null): MembershipPlan
    {
        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        $old = $plan->toArray();
        $plan->update($data);
        $this->logCatalogUpdate('membership_plans', MembershipPlan::class, $plan, $old);

        return $plan->fresh();
    }

    public function delete(MembershipPlan $plan): void
    {
        $this->logCatalogDelete('membership_plans', MembershipPlan::class, $plan);
        $plan->delete();
    }
}
