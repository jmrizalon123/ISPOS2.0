<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    /** @var array<string, mixed> */
    protected array $cache = [];

    public function get(string $key, mixed $default = null, ?string $companyId = null, ?string $storeId = null, ?string $registerId = null): mixed
    {
        $cacheKey = implode(':', array_filter([$key, $companyId, $storeId, $registerId]));

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $hierarchy = [
            [Setting::SCOPE_REGISTER, $registerId],
            [Setting::SCOPE_STORE, $storeId],
            [Setting::SCOPE_COMPANY, $companyId],
            [Setting::SCOPE_SYSTEM, null],
        ];

        foreach ($hierarchy as [$scope, $scopeId]) {
            if ($scope !== Setting::SCOPE_SYSTEM && empty($scopeId)) {
                continue;
            }

            $setting = Setting::query()
                ->where('scope', $scope)
                ->where('scope_id', $scopeId)
                ->where('key', $key)
                ->first();

            if ($setting) {
                $value = $setting->value;
                if (is_array($value) && array_key_exists('value', $value) && count($value) === 1) {
                    $value = $value['value'];
                }

                $this->cache[$cacheKey] = $value;

                return $value;
            }
        }

        $this->cache[$cacheKey] = $default;

        return $default;
    }

    public function set(string $key, mixed $value, string $scope, ?string $scopeId = null): Setting
    {
        $setting = Setting::updateOrCreate(
            [
                'scope' => $scope,
                'scope_id' => $scopeId,
                'key' => $key,
            ],
            [
                'value' => is_array($value) ? $value : ['value' => $value],
            ],
        );

        $this->cache = [];

        return $setting;
    }

    public function getForScopes(string $scope, ?string $scopeId): array
    {
        return Setting::query()
            ->where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->pluck('value', 'key')
            ->toArray();
    }
}
