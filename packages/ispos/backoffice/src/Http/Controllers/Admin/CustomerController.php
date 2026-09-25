<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use App\Domains\Crm\Services\CustomerMembershipService;
use App\Domains\Crm\Services\CustomerService;
use App\Domains\Crm\Services\LoyaltyTransactionService;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ProvidesCompanyOptions;
use Ispos\Backoffice\Http\Controllers\Admin\Concerns\ResolvesCatalogIndexFilters;
use Ispos\Backoffice\Http\Controllers\Controller;
use Ispos\Backoffice\Http\Requests\Admin\AdjustCustomerLoyaltyRequest;
use Ispos\Backoffice\Http\Requests\Admin\AssignCustomerMembershipRequest;
use Ispos\Backoffice\Http\Requests\Admin\StoreCustomerRequest;
use Ispos\Backoffice\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\LoyaltyProgram;
use App\Models\MembershipPlan;
use App\Models\PriceGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    use ProvidesCompanyOptions, ResolvesCatalogIndexFilters;

    public function __construct(
        protected CustomerService $customerService,
        protected LoyaltyTransactionService $loyaltyTransactionService,
        protected CustomerMembershipService $customerMembershipService,
    ) {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request): Response
    {
        $ctx = $this->resolveCatalogIndexFilters($request);

        return Inertia::render('Admin/Crm/Customers/Index', [
            'customers' => $this->customerService->paginate(
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
        return Inertia::render('Admin/Crm/Customers/Form', [
            'customer' => null,
            'companies' => $this->companyOptions($request),
            'loyaltyPrograms' => $this->loyaltyProgramOptions($request),
            'priceGroups' => $this->priceGroupOptions($request),
            'membershipPlans' => $this->membershipPlanOptions($request),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->customerService->create($request->validated(), $request->user());

        return redirect()->route('admin.customers.index')->with('success', 'Customer created.');
    }

    public function edit(Request $request, Customer $customer): Response
    {
        return Inertia::render('Admin/Crm/Customers/Form', [
            'customer' => $customer->load([
                'company:id,name',
                'loyaltyProgram:id,name,program_code',
                'priceGroup:id,name,group_code',
                'memberships' => fn ($q) => $q->with('membershipPlan:id,name,plan_code')->latest('started_at')->limit(5),
                'loyaltyTransactions' => fn ($q) => $q->with('creator:id,name')->limit(10),
            ]),
            'companies' => $this->companyOptions($request),
            'loyaltyPrograms' => $this->loyaltyProgramOptions($request, $customer->company_id),
            'priceGroups' => $this->priceGroupOptions($request, $customer->company_id),
            'membershipPlans' => $this->membershipPlanOptions($request, $customer->company_id),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->customerService->update($customer, $request->validated(), $request->user());

        return redirect()->route('admin.customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->customerService->delete($customer);

        return redirect()->route('admin.customers.index')->with('success', 'Customer deleted.');
    }

    public function adjustLoyalty(AdjustCustomerLoyaltyRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('adjustLoyalty', $customer);

        $this->loyaltyTransactionService->adjust(
            $customer,
            (float) $request->validated('points_delta'),
            $request->validated('notes'),
            $request->user(),
        );

        return back()->with('success', 'Loyalty points adjusted.');
    }

    public function assignMembership(AssignCustomerMembershipRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('assignMembership', $customer);

        $plan = MembershipPlan::query()->findOrFail($request->validated('membership_plan_id'));
        $this->customerMembershipService->assign($customer, $plan, $request->user());

        return back()->with('success', 'Membership assigned.');
    }

    /** @return list<array{id: string, name: string, program_code: string}> */
    protected function loyaltyProgramOptions(Request $request, ?string $companyId = null): array
    {
        return LoyaltyProgram::query()
            ->when(! $request->user()?->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $request->user()->company_id))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'program_code'])
            ->all();
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

    /** @return list<array{id: string, name: string, plan_code: string}> */
    protected function membershipPlanOptions(Request $request, ?string $companyId = null): array
    {
        return MembershipPlan::query()
            ->when(! $request->user()?->hasGlobalOrganizationAccess(), fn ($q) => $q->where('company_id', $request->user()->company_id))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'plan_code'])
            ->all();
    }
}
