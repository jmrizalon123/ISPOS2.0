<?php

namespace App\Domains\Crm\Services;

use App\Domains\Catalog\Services\Concerns\AuditsCatalogChanges;
use App\Domains\Catalog\Services\Concerns\PaginatesCompanyCatalog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CustomerService
{
    use AuditsCatalogChanges, PaginatesCompanyCatalog;

    public function paginate(User $user, ?string $search = null, ?string $companyId = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCompanyCatalog(
            $user,
            Customer::class,
            ['customer_code', 'first_name', 'last_name', 'email', 'phone', 'mobile'],
            $search,
            $companyId,
            $perPage,
            function ($q) use ($status) {
                $this->applyCatalogStatusFilter($q, $status);
                $q->with(['loyaltyProgram:id,name,program_code', 'priceGroup:id,name,group_code'])
                    ->orderBy('first_name')
                    ->orderBy('last_name');
            },
        );
    }

    /** @return Collection<int, Customer> */
    public function searchForPos(User $user, string $companyId, ?string $query, int $limit = 10): Collection
    {
        if (! $query || strlen(trim($query)) < 2) {
            return collect();
        }

        $term = trim($query);

        return Customer::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->where(function ($q) use ($term) {
                $q->where('customer_code', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%");
            })
            ->with(['memberships' => fn ($q) => $q->where('status', 'active')->with('membershipPlan:id,name,plan_code')])
            ->orderBy('first_name')
            ->limit($limit)
            ->get();
    }

    public function create(array $data, ?User $actor = null): Customer
    {
        $this->stampActor($data, $actor);
        $customer = Customer::create($data);
        $this->logCatalogCreate('customers', Customer::class, $customer);

        return $customer;
    }

    public function update(Customer $customer, array $data, ?User $actor = null): Customer
    {
        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        $old = $customer->toArray();
        $customer->update($data);
        $this->logCatalogUpdate('customers', Customer::class, $customer, $old);

        return $customer->fresh();
    }

    public function delete(Customer $customer): void
    {
        $this->logCatalogDelete('customers', Customer::class, $customer);
        $customer->delete();
    }
}
