<?php

namespace App\Support;

use Illuminate\Support\Str;

class ErrorReference
{
    public static function generate(): string
    {
        return 'ERR-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));
    }
}
