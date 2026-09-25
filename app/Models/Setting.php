<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasUlids;

    public const SCOPE_SYSTEM = 'system';

    public const SCOPE_COMPANY = 'company';

    public const SCOPE_STORE = 'store';

    public const SCOPE_REGISTER = 'register';

    protected $fillable = [
        'scope',
        'scope_id',
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
