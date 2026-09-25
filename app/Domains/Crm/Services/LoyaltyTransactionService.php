<?php

namespace App\Domains\Crm\Services;

use App\Models\Customer;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTransaction;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoyaltyTransactionService
{
    public function __construct(protected LoyaltyProgramService $loyaltyProgramService) {}

    public function earnForSale(Sale $sale, ?User $actor = null): ?LoyaltyTransaction
    {
        if (! $sale->customer_id) {
            return null;
        }

        $customer = Customer::query()->find($sale->customer_id);
        if (! $customer) {
            return null;
        }

        $program = $customer->loyaltyProgram
            ?? $this->loyaltyProgramService->defaultForCompany($sale->company_id);

        if (! $program || (float) $program->earn_rate <= 0) {
            return null;
        }

        $points = bcmul((string) $sale->grand_total, (string) $program->earn_rate, 4);
        if (bccomp($points, '0', 4) <= 0) {
            return null;
        }

        return DB::transaction(function () use ($customer, $program, $points, $sale, $actor) {
            $customer->refresh();
            $newBalance = bcadd((string) $customer->loyalty_points, $points, 4);

            $transaction = LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'loyalty_program_id' => $program->id,
                'transaction_type' => 'earn',
                'points_delta' => $points,
                'balance_after' => $newBalance,
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'notes' => "Earned from sale {$sale->sale_number}",
                'created_by' => $actor?->id,
            ]);

            $customer->update(['loyalty_points' => $newBalance]);
            $sale->update(['loyalty_points_earned' => $points]);

            return $transaction;
        });
    }

    public function reverseForSale(Sale $sale, ?User $actor = null): void
    {
        if (bccomp((string) $sale->loyalty_points_earned, '0', 4) <= 0 || ! $sale->customer_id) {
            return;
        }

        DB::transaction(function () use ($sale, $actor) {
            $customer = Customer::query()->lockForUpdate()->find($sale->customer_id);
            if (! $customer) {
                return;
            }

            $points = (string) $sale->loyalty_points_earned;
            $newBalance = bcsub((string) $customer->loyalty_points, $points, 4);
            if (bccomp($newBalance, '0', 4) < 0) {
                $newBalance = '0.0000';
            }

            LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'loyalty_program_id' => $customer->loyalty_program_id,
                'transaction_type' => 'void_reversal',
                'points_delta' => bcsub('0', $points, 4),
                'balance_after' => $newBalance,
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'notes' => "Reversed for voided sale {$sale->sale_number}",
                'created_by' => $actor?->id,
            ]);

            $customer->update(['loyalty_points' => $newBalance]);
        });
    }

    public function adjust(Customer $customer, float $pointsDelta, ?string $notes, User $actor): LoyaltyTransaction
    {
        if ($pointsDelta == 0.0) {
            throw ValidationException::withMessages(['points_delta' => 'Adjustment amount cannot be zero.']);
        }

        return DB::transaction(function () use ($customer, $pointsDelta, $notes, $actor) {
            $customer->refresh();
            $newBalance = bcadd((string) $customer->loyalty_points, (string) $pointsDelta, 4);

            if (bccomp($newBalance, '0', 4) < 0) {
                throw ValidationException::withMessages(['points_delta' => 'Insufficient loyalty points.']);
            }

            $transaction = LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'loyalty_program_id' => $customer->loyalty_program_id,
                'transaction_type' => 'adjust',
                'points_delta' => (string) $pointsDelta,
                'balance_after' => $newBalance,
                'notes' => $notes,
                'created_by' => $actor->id,
            ]);

            $customer->update(['loyalty_points' => $newBalance]);

            return $transaction;
        });
    }
}
