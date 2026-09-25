<?php

namespace App\Support;

class Locale
{
    /**
     * @return list<string>
     */
    public static function supported(): array
    {
        return array_keys(config('backoffice.locales.supported', []));
    }

    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::supported(), true);
    }

    public static function normalize(?string $locale): string
    {
        $default = config('backoffice.locales.default', 'en');

        if ($locale && self::isSupported($locale)) {
            return $locale;
        }

        return $default;
    }

    /**
     * @return array<string, array{name: string, native: string, google: string}>
     */
    public static function catalog(): array
    {
        return config('backoffice.locales.supported', []);
    }

    /**
     * @return list<string>
     */
    public static function googleLanguageCodes(): array
    {
        return array_values(array_map(
            fn (array $meta) => $meta['google'],
            self::catalog(),
        ));
    }
}
