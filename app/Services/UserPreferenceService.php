<?php

namespace App\Services;

use App\Models\User;
use App\Support\Locale;

class UserPreferenceService
{
    /** @var list<string> */
    public const ACCENT_PRESETS = ['teal', 'emerald', 'blue', 'indigo', 'violet', 'rose', 'amber', 'slate'];

    /** @var list<string> */
    public const SIDEBAR_STYLES = ['light', 'dark', 'accent', 'minimal'];

    /** @var list<string> */
    public const TOPBAR_STYLES = ['default', 'glass', 'solid', 'accent'];

    /** @var list<string> */
    public const THEME_MODES = ['light', 'dark', 'system'];

    /** @var list<string> */
    public const THEME_PRESETS = [
        'cupertino',
        'sonoma',
        'monterey',
        'graphite',
        'midnight',
        'daybreak',
        'canvas',
        'bloom',
        'obsidian',
        'aurora',
    ];

    /** @var list<string> */
    public const COLOR_MODES = ['light', 'dark'];

    /**
     * @return array<string, mixed>
     */
    public function defaultAppearanceState(): array
    {
        return [
            'accentSource' => 'preset',
            'accent' => 'blue',
            'customAccentColor' => '#2563eb',
            'sidebar' => 'light',
            'topbar' => 'default',
            'customSidebarColor' => null,
            'customTopbarColor' => null,
        ];
    }

    /**
     * @return array{theme: string, appearance: array{light: array<string, mixed>, dark: array<string, mixed>}}
     */
    public function defaults(): array
    {
        $state = $this->defaultAppearanceState();

        return [
            'theme' => 'system',
            'themePreset' => null,
            'locale' => config('backoffice.locales.default', 'en'),
            'appearance' => [
                'light' => $state,
                'dark' => array_merge($state, ['sidebar' => 'dark']),
            ],
        ];
    }

    /**
     * @return array{theme: string, appearance: array{light: array<string, mixed>, dark: array<string, mixed>}}
     */
    public function resolve(?User $user): array
    {
        if (! $user) {
            return $this->defaults();
        }

        $stored = is_array($user->preferences) ? $user->preferences : [];

        $appearance = is_array($stored['appearance'] ?? null) ? $stored['appearance'] : [];

        return $this->normalize([
            'theme' => $stored['theme'] ?? $this->defaults()['theme'],
            'themePreset' => $stored['themePreset'] ?? ($appearance['themePreset'] ?? null),
            'locale' => $stored['locale'] ?? $this->defaults()['locale'],
            'appearance' => $appearance,
        ]);
    }

    public function updateLocale(User $user, string $locale): array
    {
        $locale = Locale::normalize($locale);
        $stored = is_array($user->preferences) ? $user->preferences : [];
        $normalized = $this->normalize(array_merge($stored, ['locale' => $locale]));

        $user->update(['preferences' => $normalized]);

        return $normalized;
    }

    /**
     * @param  array{theme?: string, appearance?: mixed}  $data
     * @return array{theme: string, appearance: array{light: array<string, mixed>, dark: array<string, mixed>}}
     */
    public function update(User $user, array $data): array
    {
        $appearance = is_array($data['appearance'] ?? null) ? $data['appearance'] : [];

        $normalized = $this->normalize([
            'theme' => $data['theme'] ?? $this->defaults()['theme'],
            'themePreset' => $data['themePreset'] ?? ($appearance['themePreset'] ?? null),
            'locale' => $data['locale'] ?? ($user->preferences['locale'] ?? $this->defaults()['locale']),
            'appearance' => $appearance,
        ]);

        $user->update(['preferences' => $normalized]);

        return $normalized;
    }

    /**
     * @param  array{theme?: mixed, appearance?: mixed}  $prefs
     * @return array{theme: string, appearance: array{light: array<string, mixed>, dark: array<string, mixed>}}
     */
    public function normalize(array $prefs): array
    {
        $defaults = $this->defaults();

        $theme = $prefs['theme'] ?? $defaults['theme'];
        if (! in_array($theme, self::THEME_MODES, true)) {
            $theme = $defaults['theme'];
        }

        $themePreset = $prefs['themePreset'] ?? null;
        if ($themePreset !== null && ! in_array($themePreset, self::THEME_PRESETS, true)) {
            $themePreset = null;
        }

        $locale = $prefs['locale'] ?? $defaults['locale'];
        if (! Locale::isSupported($locale)) {
            $locale = $defaults['locale'];
        }

        return [
            'theme' => $theme,
            'themePreset' => $themePreset,
            'locale' => $locale,
            'appearance' => $this->normalizeModeAppearanceMap($prefs['appearance'] ?? []),
        ];
    }

    /**
     * @return array{light: array<string, mixed>, dark: array<string, mixed>}
     */
    protected function normalizeModeAppearanceMap(mixed $appearance): array
    {
        $defaults = $this->defaults()['appearance'];

        if (! is_array($appearance)) {
            return $defaults;
        }

        if (array_key_exists('accentSource', $appearance) || array_key_exists('accent', $appearance)) {
            $legacy = $this->normalizeAppearanceState($appearance);

            return [
                'light' => $legacy,
                'dark' => $legacy,
            ];
        }

        return [
            'light' => $this->normalizeAppearanceState(is_array($appearance['light'] ?? null) ? $appearance['light'] : [], $defaults['light']),
            'dark' => $this->normalizeAppearanceState(is_array($appearance['dark'] ?? null) ? $appearance['dark'] : [], $defaults['dark']),
        ];
    }

    /**
     * @param  array<string, mixed>  $appearance
     * @param  array<string, mixed>|null  $fallback
     * @return array<string, mixed>
     */
    protected function normalizeAppearanceState(array $appearance, ?array $fallback = null): array
    {
        $defaults = $fallback ?? $this->defaultAppearanceState();

        $accentSource = ($appearance['accentSource'] ?? $defaults['accentSource']) === 'custom'
            ? 'custom'
            : 'preset';

        $accent = $appearance['accent'] ?? $defaults['accent'];
        if (! in_array($accent, self::ACCENT_PRESETS, true)) {
            $accent = $defaults['accent'];
        }

        $customAccentColor = $appearance['customAccentColor'] ?? $defaults['customAccentColor'];
        if (! is_string($customAccentColor) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $customAccentColor)) {
            $customAccentColor = $defaults['customAccentColor'];
        }

        $sidebar = $appearance['sidebar'] ?? $defaults['sidebar'];
        if (! in_array($sidebar, self::SIDEBAR_STYLES, true)) {
            $sidebar = $defaults['sidebar'];
        }

        $topbar = $appearance['topbar'] ?? $defaults['topbar'];
        if (! in_array($topbar, self::TOPBAR_STYLES, true)) {
            $topbar = $defaults['topbar'];
        }

        return [
            'accentSource' => $accentSource,
            'accent' => $accent,
            'customAccentColor' => $customAccentColor,
            'sidebar' => $sidebar,
            'topbar' => $topbar,
            'customSidebarColor' => $this->normalizeOptionalHex($appearance['customSidebarColor'] ?? null),
            'customTopbarColor' => $this->normalizeOptionalHex($appearance['customTopbarColor'] ?? null),
        ];
    }

    protected function normalizeOptionalHex(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return null;
        }

        return $value;
    }
}
