<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesProductFields;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest, ValidatesProductFields;

    public function authorize(): bool
    {
        return $this->user()?->can('products.create') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id') ?: $this->user()?->company_id;

        return $this->productRules(null, $companyId);
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCompanyId();
        $this->merge([
            'track_inventory' => $this->boolean('track_inventory'),
            'has_variants' => $this->boolean('has_variants'),
            'has_modifiers' => $this->boolean('has_modifiers'),
            'has_components' => $this->boolean('has_components'),
            ...$this->normalizedImageRows(),
        ]);
    }

    /** @return array<string, mixed> */
    protected function normalizedImageRows(): array
    {
        if (! is_array($this->input('images'))) {
            return [];
        }

        return [
            'images' => collect($this->input('images'))
                ->map(function ($row) {
                    if (! is_array($row)) {
                        return $row;
                    }

                    $row['is_default'] = filter_var($row['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    return $row;
                })
                ->all(),
        ];
    }
}
