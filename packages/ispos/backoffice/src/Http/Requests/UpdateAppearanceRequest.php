<?php

namespace Ispos\Backoffice\Http\Requests;

use App\Services\UserPreferenceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $appearanceRules = [
            'accentSource' => ['required', Rule::in(['preset', 'custom'])],
            'accent' => ['required', Rule::in(UserPreferenceService::ACCENT_PRESETS)],
            'customAccentColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sidebar' => ['required', Rule::in(UserPreferenceService::SIDEBAR_STYLES)],
            'topbar' => ['required', Rule::in(UserPreferenceService::TOPBAR_STYLES)],
            'customSidebarColor' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'customTopbarColor' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];

        $rules = [
            'theme' => ['required', Rule::in(UserPreferenceService::THEME_MODES)],
            'appearance' => ['required', 'array'],
            'appearance.themePreset' => ['nullable', 'string', Rule::in(UserPreferenceService::THEME_PRESETS)],
            'appearance.light' => ['required', 'array'],
            'appearance.dark' => ['required', 'array'],
        ];

        foreach (UserPreferenceService::COLOR_MODES as $mode) {
            foreach ($appearanceRules as $field => $fieldRules) {
                $rules["appearance.{$mode}.{$field}"] = $fieldRules;
            }
        }

        return $rules;
    }
}
