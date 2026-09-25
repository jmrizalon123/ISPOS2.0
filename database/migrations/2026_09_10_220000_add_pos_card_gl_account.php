<?php

use App\Models\ChartOfAccount;
use App\Models\GlAccountMapping;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $companyIds = GlAccountMapping::query()
            ->where('mapping_key', GlAccountMapping::POS_CASH)
            ->pluck('company_id')
            ->unique();

        foreach ($companyIds as $companyId) {
            if (GlAccountMapping::query()
                ->where('company_id', $companyId)
                ->where('mapping_key', GlAccountMapping::POS_CARD)
                ->exists()) {
                continue;
            }

            $account = ChartOfAccount::query()->firstOrCreate(
                ['company_id' => $companyId, 'account_code' => '1020'],
                [
                    'account_name' => 'Card Clearing',
                    'account_type' => 'asset',
                    'normal_balance' => 'debit',
                    'is_system' => true,
                    'status' => 'active',
                ],
            );

            GlAccountMapping::query()->create([
                'company_id' => $companyId,
                'mapping_key' => GlAccountMapping::POS_CARD,
                'chart_of_account_id' => $account->id,
            ]);
        }
    }

    public function down(): void
    {
        GlAccountMapping::query()
            ->where('mapping_key', GlAccountMapping::POS_CARD)
            ->delete();
    }
};
