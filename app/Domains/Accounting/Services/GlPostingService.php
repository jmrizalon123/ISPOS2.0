<?php

namespace App\Domains\Accounting\Services;

use App\Models\Company;
use App\Models\GlAccountMapping;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GlPostingService
{
    public function __construct(protected AuditLogger $auditLogger)
    {
    }

    public function postSale(Sale $sale, User $user): ?JournalEntry
    {
        $company = Company::query()->find($sale->company_id);
        if (! $company?->enable_accounting) {
            return null;
        }

        if ($this->hasPostedEntry($sale)) {
            return null;
        }

        $sale->loadMissing('payments');

        $tax = (string) $sale->tax_total;
        $cashAmount = $this->sumPaymentsByMethod($sale, 'cash');
        $cardAmount = $this->sumPaymentsByMethod($sale, 'card');

        // Legacy / sync sales without payment rows: treat full total as cash.
        if (bccomp($cashAmount, '0', 4) === 0 && bccomp($cardAmount, '0', 4) === 0) {
            $cashAmount = (string) $sale->grand_total;
        }

        $requiresCard = bccomp($cardAmount, '0', 4) === 1;
        $accounts = $this->resolvePosMappings($company->id, bccomp($tax, '0', 4) === 1, $requiresCard);

        return DB::transaction(function () use ($sale, $user, $accounts, $tax, $cashAmount, $cardAmount) {
            $entry = JournalEntry::create([
                'company_id' => $sale->company_id,
                'entry_number' => $this->nextSystemEntryNumber($sale->company_id, 'SALE'),
                'entry_date' => ($sale->completed_at ?? now())->toDateString(),
                'description' => "POS sale {$sale->sale_number}",
                'status' => 'posted',
                'source_type' => Sale::class,
                'source_id' => $sale->id,
                'posted_at' => now(),
                'posted_by' => $user->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $netRevenue = bcsub((string) $sale->subtotal, (string) $sale->discount_total, 4);
            $lines = [];

            if (bccomp($cashAmount, '0', 4) === 1) {
                $lines[] = [
                    'account' => $accounts[GlAccountMapping::POS_CASH],
                    'debit' => $cashAmount,
                    'credit' => '0',
                    'description' => 'Cash received',
                ];
            }

            if (bccomp($cardAmount, '0', 4) === 1) {
                $lines[] = [
                    'account' => $accounts[GlAccountMapping::POS_CARD],
                    'debit' => $cardAmount,
                    'credit' => '0',
                    'description' => 'Card received',
                ];
            }

            $lines[] = [
                'account' => $accounts[GlAccountMapping::POS_SALES_REVENUE],
                'debit' => '0',
                'credit' => $netRevenue,
                'description' => 'Sales revenue',
            ];

            if (bccomp($tax, '0', 4) === 1) {
                $lines[] = [
                    'account' => $accounts[GlAccountMapping::POS_OUTPUT_TAX],
                    'debit' => '0',
                    'credit' => $tax,
                    'description' => 'Output VAT',
                ];
            }

            foreach ($lines as $index => $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $line['account']->id,
                    'line_number' => $index + 1,
                    'description' => $line['description'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                ]);
            }

            $this->auditLogger->log('post', 'accounting', JournalEntry::class, $entry->id, null, [
                'entry_number' => $entry->entry_number,
                'source' => 'sale',
                'sale_number' => $sale->sale_number,
            ], $user);

            return $entry->fresh(['lines.chartOfAccount']);
        });
    }

    protected function sumPaymentsByMethod(Sale $sale, string $method): string
    {
        $total = '0.0000';
        foreach ($sale->payments as $payment) {
            if ($payment->payment_method === $method) {
                $total = bcadd($total, (string) $payment->amount, 4);
            }
        }

        return $total;
    }

    public function reverseSale(Sale $sale, User $user): ?JournalEntry
    {
        $company = Company::query()->find($sale->company_id);
        if (! $company?->enable_accounting) {
            return null;
        }

        $original = JournalEntry::query()
            ->where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->where('status', 'posted')
            ->where('description', 'not like', 'Reversal:%')
            ->first();

        if (! $original) {
            return null;
        }

        if ($this->hasReversalEntry($sale)) {
            return null;
        }

        return DB::transaction(function () use ($sale, $user, $original) {
            $original->load('lines');

            $entry = JournalEntry::create([
                'company_id' => $sale->company_id,
                'entry_number' => $this->nextSystemEntryNumber($sale->company_id, 'VOID'),
                'entry_date' => now()->toDateString(),
                'description' => "Reversal: POS sale {$sale->sale_number}",
                'status' => 'posted',
                'source_type' => Sale::class,
                'source_id' => $sale->id,
                'posted_at' => now(),
                'posted_by' => $user->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($original->lines as $index => $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'chart_of_account_id' => $line->chart_of_account_id,
                    'line_number' => $index + 1,
                    'description' => 'Void reversal',
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                ]);
            }

            $this->auditLogger->log('void', 'accounting', JournalEntry::class, $entry->id, null, [
                'entry_number' => $entry->entry_number,
                'source' => 'sale_void',
                'sale_number' => $sale->sale_number,
            ], $user);

            return $entry->fresh(['lines.chartOfAccount']);
        });
    }

    public function postPurchaseReceipt(PurchaseReceipt $receipt, User $user): ?JournalEntry
    {
        $company = Company::query()->find($receipt->company_id);
        if (! $company?->enable_accounting) {
            return null;
        }

        if ($this->hasSourceEntry(PurchaseReceipt::class, $receipt->id)) {
            return null;
        }

        $amount = (string) $receipt->total_amount;
        if (bccomp($amount, '0', 4) <= 0) {
            return null;
        }

        $accounts = $this->resolvePurchaseMappings($company->id);

        return DB::transaction(function () use ($receipt, $user, $amount, $accounts) {
            $entry = $this->createPostedEntry(
                $receipt->company_id,
                'RCV',
                $receipt->receipt_date->toDateString(),
                "Purchase receipt {$receipt->receipt_number}",
                PurchaseReceipt::class,
                $receipt->id,
                $user,
            );

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $accounts[GlAccountMapping::PURCHASE_INVENTORY]->id,
                'line_number' => 1,
                'description' => 'Inventory received',
                'debit' => $amount,
                'credit' => '0',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $accounts[GlAccountMapping::PURCHASE_AP]->id,
                'line_number' => 2,
                'description' => 'Accounts payable',
                'debit' => '0',
                'credit' => $amount,
            ]);

            $this->auditLogger->log('post', 'accounting', JournalEntry::class, $entry->id, null, [
                'entry_number' => $entry->entry_number,
                'source' => 'purchase_receipt',
                'receipt_number' => $receipt->receipt_number,
            ], $user);

            return $entry->fresh(['lines.chartOfAccount']);
        });
    }

    public function reversePurchaseReturn(PurchaseReturn $purchaseReturn, User $user): ?JournalEntry
    {
        $company = Company::query()->find($purchaseReturn->company_id);
        if (! $company?->enable_accounting) {
            return null;
        }

        if ($this->hasSourceEntry(PurchaseReturn::class, $purchaseReturn->id)) {
            return null;
        }

        $amount = (string) $purchaseReturn->lines()->sum('line_total');
        if (bccomp($amount, '0', 4) <= 0) {
            return null;
        }

        $accounts = $this->resolvePurchaseMappings($company->id);

        return DB::transaction(function () use ($purchaseReturn, $user, $amount, $accounts) {
            $entry = $this->createPostedEntry(
                $purchaseReturn->company_id,
                'PRET',
                now()->toDateString(),
                "Purchase return {$purchaseReturn->return_number}",
                PurchaseReturn::class,
                $purchaseReturn->id,
                $user,
            );

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $accounts[GlAccountMapping::PURCHASE_AP]->id,
                'line_number' => 1,
                'description' => 'Reduce AP',
                'debit' => $amount,
                'credit' => '0',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $accounts[GlAccountMapping::PURCHASE_INVENTORY]->id,
                'line_number' => 2,
                'description' => 'Inventory returned',
                'debit' => '0',
                'credit' => $amount,
            ]);

            $this->auditLogger->log('post', 'accounting', JournalEntry::class, $entry->id, null, [
                'entry_number' => $entry->entry_number,
                'source' => 'purchase_return',
                'return_number' => $purchaseReturn->return_number,
            ], $user);

            return $entry->fresh(['lines.chartOfAccount']);
        });
    }

    public function postSupplierPayment(SupplierPayment $payment, User $user): ?JournalEntry
    {
        $company = Company::query()->find($payment->company_id);
        if (! $company?->enable_accounting) {
            return null;
        }

        if ($this->hasSourceEntry(SupplierPayment::class, $payment->id)) {
            return null;
        }

        $amount = (string) $payment->amount;
        $purchaseAccounts = $this->resolvePurchaseMappings($company->id);
        $posAccounts = $this->resolvePosMappings($company->id, false);

        return DB::transaction(function () use ($payment, $user, $amount, $purchaseAccounts, $posAccounts) {
            $entry = $this->createPostedEntry(
                $payment->company_id,
                'PAY',
                $payment->payment_date->toDateString(),
                "Supplier payment {$payment->payment_number}",
                SupplierPayment::class,
                $payment->id,
                $user,
            );

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $purchaseAccounts[GlAccountMapping::PURCHASE_AP]->id,
                'line_number' => 1,
                'description' => 'Pay supplier',
                'debit' => $amount,
                'credit' => '0',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'chart_of_account_id' => $posAccounts[GlAccountMapping::POS_CASH]->id,
                'line_number' => 2,
                'description' => 'Cash paid',
                'debit' => '0',
                'credit' => $amount,
            ]);

            $this->auditLogger->log('post', 'accounting', JournalEntry::class, $entry->id, null, [
                'entry_number' => $entry->entry_number,
                'source' => 'supplier_payment',
                'payment_number' => $payment->payment_number,
            ], $user);

            return $entry->fresh(['lines.chartOfAccount']);
        });
    }

    /** @return array<string, \App\Models\ChartOfAccount> */
    protected function resolvePurchaseMappings(string $companyId): array
    {
        return $this->resolveMappings($companyId, [
            GlAccountMapping::PURCHASE_INVENTORY,
            GlAccountMapping::PURCHASE_AP,
        ]);
    }

    /** @return array<string, \App\Models\ChartOfAccount> */
    protected function resolvePosMappings(
        string $companyId,
        bool $requiresOutputTax = true,
        bool $requiresCard = false,
    ): array {
        $keys = [
            GlAccountMapping::POS_CASH,
            GlAccountMapping::POS_SALES_REVENUE,
        ];

        if ($requiresOutputTax) {
            $keys[] = GlAccountMapping::POS_OUTPUT_TAX;
        }

        if ($requiresCard) {
            $keys[] = GlAccountMapping::POS_CARD;
        }

        return $this->resolveMappings($companyId, $keys);
    }

    /** @param  list<string>  $keys
     * @return array<string, \App\Models\ChartOfAccount>
     */
    protected function resolveMappings(string $companyId, array $keys): array
    {
        $mappings = GlAccountMapping::query()
            ->where('company_id', $companyId)
            ->whereIn('mapping_key', $keys)
            ->with('chartOfAccount')
            ->get()
            ->keyBy('mapping_key');

        foreach ($keys as $key) {
            if (! $mappings->has($key)) {
                throw ValidationException::withMessages([
                    'accounting' => "Missing GL mapping: {$key}. Seed or configure chart of accounts.",
                ]);
            }
        }

        return $mappings->map(fn ($mapping) => $mapping->chartOfAccount)->all();
    }

    protected function hasSourceEntry(string $sourceType, string $sourceId): bool
    {
        return JournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'posted')
            ->exists();
    }

    protected function createPostedEntry(
        string $companyId,
        string $prefix,
        string $entryDate,
        string $description,
        string $sourceType,
        string $sourceId,
        User $user,
    ): JournalEntry {
        return JournalEntry::create([
            'company_id' => $companyId,
            'entry_number' => $this->nextSystemEntryNumber($companyId, $prefix),
            'entry_date' => $entryDate,
            'description' => $description,
            'status' => 'posted',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'posted_at' => now(),
            'posted_by' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    protected function hasPostedEntry(Sale $sale): bool
    {
        return JournalEntry::query()
            ->where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->where('status', 'posted')
            ->where('description', 'not like', 'Reversal:%')
            ->exists();
    }

    protected function hasReversalEntry(Sale $sale): bool
    {
        return JournalEntry::query()
            ->where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->where('status', 'posted')
            ->where('description', 'like', 'Reversal:%')
            ->exists();
    }

    protected function nextSystemEntryNumber(string $companyId, string $prefix): string
    {
        $count = JournalEntry::query()
            ->where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->count();

        return sprintf('%s-%s-%04d', $prefix, now()->format('Ymd'), $count + 1);
    }
}
