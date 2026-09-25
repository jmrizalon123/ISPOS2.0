<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Crm\Services\MembershipPlanService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\StoreMembershipPlanRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateMembershipPlanRequest;
use App\Models\MembershipPlan;
use App\Models\PriceGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembershipPlanController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(protected MembershipPlanService $membershipPlanService)
    {
        $this->authorizeResource(MembershipPlan::class, 'membership_plan');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/Crm/MembershipPlans/Index', [
            'membershipPlans' => $this->membershipPlanService->paginate(
                $request->user(),
                $request->string('search')->toString() ?: null,
                $ctx['companyId'],
                $ctx['status'],
            ),
            'companies' => $this->companyOptions($request),
            'filters' => $ctx['filters'],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Crm/MembershipPlans/Form', [
            'membershipPlan' => null,
            'companies' => $this->companyOptions($request),
            'priceGroups' => $this->priceGroupOptions($request),
        ]);
    }

    public function store(StoreMembershipPlanRequest $request): RedirectResponse
    {
        $this->membershipPlanService->create($request->validated(), $request->user());

        return redirect()->route('admin.membership-plans.index')->with('success', 'Membership plan created.');
    }

    public function edit(Request $request, MembershipPlan $membershipPlan): Response
    {
        return Inertia::render('Admin/Crm/MembershipPlans/Form', [
            'membershipPlan' => $membershipPlan->load(['company:id,name', 'priceGroup:id,name,group_code']),
            'companies' => $this->companyOptions($request),
            'priceGroups' => $this->priceGroupOptions($request, $membershipPlan->company_id),
        ]);
    }

    public function update(UpdateMembershipPlanRequest $request, MembershipPlan $membershipPlan): RedirectResponse
    {
        $this->membershipPlanService->update($membershipPlan, $request->validated(), $request->user());

        return redirect()->route('admin.membership-plans.index')->with('success', 'Membership plan updated.');
    }

    public function destroy(MembershipPlan $membershipPlan): RedirectResponse
    {
        $this->membershipPlanService->delete($membershipPlan);

        return redirect()->route('admin.membership-plans.index')->with('success', 'Membership plan deleted.');
    }

    /** @return list<array{id: string, name: string, group_code: string}> */
    protected function priceGroupOptions(Request $request, ?string $companyId = null): array
    {
        return PriceGroup::query()
            ->when(! $request->user()?->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $request->user()->company_id))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'group_code'])
            ->all();
    }
}
