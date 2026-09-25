<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlAccountMapping extends Model
{
    use HasUlids;

    public const POS_CASH = 'pos_cash';

    public const POS_CARD = 'pos_card';

    public const POS_SALES_REVENUE = 'pos_sales_revenue';

    public const POS_OUTPUT_TAX = 'pos_output_tax';

    public const PURCHASE_INVENTORY = 'purchase_inventory';

    public const PURCHASE_AP = 'purchase_ap';

    protected $fillable = [
        'company_id', 'mapping_key', 'chart_of_account_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }
}
