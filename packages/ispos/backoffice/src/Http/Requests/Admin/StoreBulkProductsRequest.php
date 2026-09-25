<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesProductFields;
use Illuminate\Foundation\Http\FormRequest;

class StoreBulkProductsRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest, ValidatesProductFields;

    public function authorize(): bool
    {
        return $this->user()?->can('products.create') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id') ?: $this->user()?->company_id;

        return $this->bulkProductRules($companyId);
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCompanyId();

        $merged = [
            'track_inventory' => $this->boolean('track_inventory'),
        ];

        foreach (['category_id', 'brand_id', 'unit_id', 'tax_id'] as $key) {
            if ($this->exists($key) && $this->input($key) === '') {
                $merged[$key] = null;
            }
        }

        $merged['items'] = collect($this->input('items'))
            ->map(function ($row) {
                if (! is_array($row)) {
                    return $row;
                }

                foreach (['description', 'barcode', 'cost', 'base_price', 'ideal_qty', 'warning_qty'] as $key) {
                    if (($row[$key] ?? '') === '') {
                        $row[$key] = null;
                    }
                }

                return $row;
            })
            ->values()
            ->all();

        $this->merge($merged);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $barcodes = collect($this->input('items', []))
                ->map(fn ($row) => is_array($row) ? trim((string) ($row['barcode'] ?? '')) : '')
                ->filter();

            $duplicates = $barcodes->duplicates();

            if ($duplicates->isEmpty()) {
                return;
            }

            foreach ($barcodes as $index => $barcode) {
                if ($duplicates->contains($barcode)) {
                    $validator->errors()->add("items.{$index}.barcode", __('validation.distinct', ['attribute' => 'barcode']));
                }
            }
        });
    }
}
