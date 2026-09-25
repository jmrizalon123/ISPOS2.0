<?php

namespace App\Support;

use Symfony\Component\Uid\Ulid as SymfonyUlid;

class Ulid
{
    public static function generate(): string
    {
        return (string) new SymfonyUlid;
    }

    public static function isValid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return SymfonyUlid::isValid($value);
    }
}
