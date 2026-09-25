<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IsposBrandingService
{
    public const KEY_NAME = 'system.ispos.name';

    public const KEY_TAGLINE = 'system.ispos.tagline';

    public const KEY_OWNERSHIP = 'system.ispos.ownership';

    public const KEY_COPYRIGHT = 'system.ispos.copyright';

    public const KEY_LOGO = 'system.ispos.logo';

    public const KEY_SUPPORT_EMAIL = 'system.ispos.support_email';

    public const KEY_SUPPORT_URL = 'system.ispos.support_url';

    public const MAX_LOGO_KILOBYTES = 2048;

    public function __construct(
        protected SettingService $settingService,
    ) {}

    /**
     * @return array{
     *     name: string,
     *     tagline: string,
     *     ownership: string,
     *     copyright: string,
     *     logo_url: string|null,
     *     initials: string,
     *     support_email: string,
     *     support_url: string
     * }
     */
    public function forFrontend(): array
    {
        $name = $this->name();

        return [
            'name' => $name,
            'tagline' => $this->tagline(),
            'ownership' => $this->ownership(),
            'copyright' => $this->copyright(),
            'logo_url' => $this->logoUrl(),
            'initials' => $this->initials($name),
            'support_email' => $this->supportEmail(),
            'support_url' => $this->supportUrl(),
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     tagline: string,
     *     ownership: string,
     *     copyright: string,
     *     logo_url: string|null,
     *     support_email: string,
     *     support_url: string
     * }
     */
    public function forAdmin(): array
    {
        return [
            'name' => $this->name(),
            'tagline' => $this->tagline(),
            'ownership' => $this->ownership(),
            'copyright' => $this->copyright(),
            'logo_url' => $this->logoUrl(),
            'support_email' => $this->supportEmail(),
            'support_url' => $this->supportUrl(),
        ];
    }

    /**
     * @param  array{
     *     name: string,
     *     tagline?: string|null,
     *     ownership?: string|null,
     *     copyright?: string|null,
     *     support_email?: string|null,
     *     support_url?: string|null,
     *     remove_logo?: bool
     * }  $data
     */
    public function update(array $data, ?UploadedFile $logo = null): void
    {
        $this->settingService->set(self::KEY_NAME, trim($data['name']), Setting::SCOPE_SYSTEM);
        $this->settingService->set(self::KEY_TAGLINE, trim((string) ($data['tagline'] ?? '')), Setting::SCOPE_SYSTEM);
        $this->settingService->set(self::KEY_OWNERSHIP, trim((string) ($data['ownership'] ?? '')), Setting::SCOPE_SYSTEM);
        $this->settingService->set(self::KEY_COPYRIGHT, trim((string) ($data['copyright'] ?? '')), Setting::SCOPE_SYSTEM);
        $this->settingService->set(self::KEY_SUPPORT_EMAIL, trim((string) ($data['support_email'] ?? '')), Setting::SCOPE_SYSTEM);
        $this->settingService->set(self::KEY_SUPPORT_URL, trim((string) ($data['support_url'] ?? '')), Setting::SCOPE_SYSTEM);

        if (! empty($data['remove_logo'])) {
            $this->deleteStoredLogo();
            $this->settingService->set(self::KEY_LOGO, '', Setting::SCOPE_SYSTEM);

            return;
        }

        if ($logo) {
            $this->deleteStoredLogo();
            $this->settingService->set(self::KEY_LOGO, $this->storeLogo($logo), Setting::SCOPE_SYSTEM);
        }
    }

    public function name(): string
    {
        $name = trim((string) $this->settingService->get(self::KEY_NAME, config('app.name', 'iSPOS')));

        return $name !== '' ? $name : 'iSPOS';
    }

    public function tagline(): string
    {
        $default = 'One login. Every store.';

        return trim((string) $this->settingService->get(self::KEY_TAGLINE, $default));
    }

    public function ownership(): string
    {
        return trim((string) $this->settingService->get(self::KEY_OWNERSHIP, ''));
    }

    public function copyright(): string
    {
        $default = '© '.date('Y').' '.$this->name().'. All rights reserved.';
        $copyright = trim((string) $this->settingService->get(self::KEY_COPYRIGHT, ''));

        return $copyright !== '' ? $copyright : $default;
    }

    public function supportEmail(): string
    {
        return trim((string) $this->settingService->get(self::KEY_SUPPORT_EMAIL, ''));
    }

    public function supportUrl(): string
    {
        return trim((string) $this->settingService->get(self::KEY_SUPPORT_URL, ''));
    }

    public function logoUrl(): ?string
    {
        $logo = trim((string) $this->settingService->get(self::KEY_LOGO, ''));

        if ($logo === '') {
            return null;
        }

        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return $logo;
        }

        return Storage::disk('public')->url($logo);
    }

    protected function storeLogo(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'png';
        $filename = 'logo-'.Str::uuid()->toString().'.'.strtolower($extension);

        return $file->storeAs('branding', $filename, 'public');
    }

    protected function deleteStoredLogo(): void
    {
        $logo = trim((string) $this->settingService->get(self::KEY_LOGO, ''));

        if ($logo === '' || str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($logo);
    }

    protected function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if ($parts === []) {
            return 'iS';
        }

        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 2));
        }

        return strtoupper(substr($parts[0], 0, 1).substr($parts[1], 0, 1));
    }
}
