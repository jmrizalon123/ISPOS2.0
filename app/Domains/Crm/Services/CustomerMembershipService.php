<?php

namespace App\Domains\Crm\Services;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerMembershipService
{
    public function assign(Customer $customer, MembershipPlan $plan, User $actor, ?\DateTimeInterface $startedAt = null): CustomerMembership
    {
        if ($plan->company_id !== $customer->company_id) {
            throw ValidationException::withMessages(['membership_plan_id' => 'Membership plan must belong to the same company.']);
        }

        if ($plan->status !== 'active') {
            throw ValidationException::withMessages(['membership_plan_id' => 'Membership plan is not active.']);
        }

        return DB::transaction(function () use ($customer, $plan, $actor, $startedAt) {
            CustomerMembership::query()
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled', 'updated_by' => $actor->id]);

            $started = $startedAt ? \Illuminate\Support\Carbon::parse($startedAt) : now();
            $expires = $plan->duration_days
                ? $started->copy()->addDays($plan->duration_days)
                : null;

            return CustomerMembership::create([
                'customer_id' => $customer->id,
                'membership_plan_id' => $plan->id,
                'status' => 'active',
                'started_at' => $started,
                'expires_at' => $expires,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }

    public function cancel(CustomerMembership $membership, User $actor): CustomerMembership
    {
        if ($membership->status !== 'active') {
            throw ValidationException::withMessages(['membership' => 'Membership is not active.']);
        }

        $membership->update([
            'status' => 'cancelled',
            'updated_by' => $actor->id,
        ]);

        return $membership->fresh();
    }
}
