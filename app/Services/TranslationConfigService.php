<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Locale;

class TranslationConfigService
{
    public const KEY_PROVIDER = 'system.translation.provider';

    public const KEY_GOOGLE_ENABLED = 'system.google_translate.enabled';

    public const KEY_GOOGLE_USE_WIDGET = 'system.google_translate.use_widget';

    public const KEY_GOOGLE_API_KEY = 'system.google_translate.api_key';

    public function __construct(
        protected SettingService $settingService,
    ) {}

    /**
     * @return array{provider: string, google: array{enabled: bool, useWidget: bool, hasApiKey: bool, pageLanguage: string, includedLanguages: string}}
     */
    public function forFrontend(): array
    {
        $provider = $this->provider();
        $googleEnabled = $this->googleEnabled();

        return [
            'provider' => $provider,
            'google' => [
                'enabled' => $googleEnabled,
                'useWidget' => $this->googleUseWidget(),
                'hasApiKey' => $this->hasApiKey(),
                'pageLanguage' => Locale::normalize(config('backoffice.locales.default', 'en')),
                'includedLanguages' => implode(',', Locale::googleLanguageCodes()),
            ],
        ];
    }

    /**
     * @return array{provider: string, google_enabled: bool, google_use_widget: bool, has_api_key: bool}
     */
    public function forAdmin(): array
    {
        return [
            'provider' => $this->provider(),
            'google_enabled' => $this->googleEnabled(),
            'google_use_widget' => $this->googleUseWidget(),
            'has_api_key' => $this->hasApiKey(),
        ];
    }

    /**
     * @param  array{provider: string, google_enabled?: bool, google_use_widget?: bool, google_api_key?: string|null}  $data
     */
    public function update(array $data): void
    {
        $provider = in_array($data['provider'], ['builtin', 'google'], true) ? $data['provider'] : 'builtin';

        $this->settingService->set(
            self::KEY_PROVIDER,
            $provider,
            Setting::SCOPE_SYSTEM,
        );

        $googleEnabled = (bool) ($data['google_enabled'] ?? false);
        $googleUseWidget = (bool) ($data['google_use_widget'] ?? true);

        if ($provider === 'google' && $googleUseWidget) {
            $googleEnabled = true;
        }

        $this->settingService->set(
            self::KEY_GOOGLE_ENABLED,
            $googleEnabled,
            Setting::SCOPE_SYSTEM,
        );

        $this->settingService->set(
            self::KEY_GOOGLE_USE_WIDGET,
            (bool) ($data['google_use_widget'] ?? true),
            Setting::SCOPE_SYSTEM,
        );

        if (! empty($data['google_api_key'])) {
            $this->settingService->set(
                self::KEY_GOOGLE_API_KEY,
                $data['google_api_key'],
                Setting::SCOPE_SYSTEM,
            );
        }
    }

    public function provider(): string
    {
        $provider = $this->settingService->get(self::KEY_PROVIDER, 'builtin');

        return in_array($provider, ['builtin', 'google'], true) ? $provider : 'builtin';
    }

    public function googleEnabled(): bool
    {
        return (bool) $this->settingService->get(self::KEY_GOOGLE_ENABLED, false);
    }

    public function googleUseWidget(): bool
    {
        return (bool) $this->settingService->get(self::KEY_GOOGLE_USE_WIDGET, true);
    }

    public function hasApiKey(): bool
    {
        $key = $this->settingService->get(self::KEY_GOOGLE_API_KEY, '');

        return is_string($key) && $key !== '';
    }
}
