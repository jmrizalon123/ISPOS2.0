<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasCatalogUuid
{
    protected static function bootHasCatalogUuid(): void
    {
        static::creating(function ($model): void {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
