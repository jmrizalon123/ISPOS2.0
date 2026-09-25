<?php

namespace App\Domains\Accounting\Services;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\GlAccountMapping;
use App\Models\User;

class DefaultChartOfAccountsService
{
    /** @return array<string, ChartOfAccount> */
    public function seedForCompany(Company $company, ?User $actor = null): array
    {
        $accounts = [];

        $definitions = [
            ['code' => '1010', 'name' => 'Cash on Hand', 'type' => 'asset', 'key' => GlAccountMapping::POS_CASH],
            ['code' => '1020', 'name' => 'Card Clearing', 'type' => 'asset', 'key' => GlAccountMapping::POS_CARD],
            ['code' => '1300', 'name' => 'Inventory Asset', 'type' => 'asset', 'key' => GlAccountMapping::PURCHASE_INVENTORY],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => 'liability', 'key' => GlAccountMapping::PURCHASE_AP],
            ['code' => '2200', 'name' => 'Output VAT Payable', 'type' => 'liability', 'key' => GlAccountMapping::POS_OUTPUT_TAX],
            ['code' => '3000', 'name' => "Owner's Equity", 'type' => 'equity', 'key' => null],
            ['code' => '4010', 'name' => 'Sales Revenue', 'type' => 'revenue', 'key' => GlAccountMapping::POS_SALES_REVENUE],
            ['code' => '5100', 'name' => 'General Expenses', 'type' => 'expense', 'key' => null],
        ];

        foreach ($definitions as $definition) {
            $account = ChartOfAccount::query()->firstOrCreate(
                ['company_id' => $company->id, 'account_code' => $definition['code']],
                [
                    'account_name' => $definition['name'],
                    'account_type' => $definition['type'],
                    'normal_balance' => ChartOfAccount::normalBalanceForType($definition['type']),
                    'is_system' => true,
                    'status' => 'active',
                    'created_by' => $actor?->id,
                    'updated_by' => $actor?->id,
                ],
            );

            $accounts[$definition['code']] = $account;

            if ($definition['key']) {
                GlAccountMapping::query()->updateOrCreate(
                    ['company_id' => $company->id, 'mapping_key' => $definition['key']],
                    ['chart_of_account_id' => $account->id],
                );
            }
        }

        return $accounts;
    }
}
