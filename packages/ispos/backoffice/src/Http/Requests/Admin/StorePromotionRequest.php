<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePromotionRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest;

    public function authorize(): bool
    {
        return $this->user()?->can('promotions.create') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id') ?: $this->user()?->company_id;

        return [
            'company_id' => ['required', 'ulid', 'exists:companies,id'],
            'promo_code' => ['required', 'string', 'max:50', Rule::unique('promotions', 'promo_code')->where(fn ($q) => $q->where('company_id', $companyId))],
            'name' => ['required', 'string', 'max:255'],
            'promo_type' => ['required', Rule::in(['percent_off', 'fixed_amount'])],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_purchase_amount' => ['nullable', 'numeric', 'min:0'],
            'applies_to' => ['required', Rule::in(['all', 'products', 'categories'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', Rule::in(['draft', 'active', 'expired', 'cancelled'])],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'product_ids' => ['array'],
            'product_ids.*' => ['ulid', 'exists:products,id'],
            'category_ids' => ['array'],
            'category_ids.*' => ['ulid', 'exists:categories,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $status = $this->input('status');
            $user = $this->user();

            if (! is_string($status) || ! $user) {
                return;
            }

            if (! $user->can("promotions.{$status}")) {
                $validator->errors()->add('status', 'You do not have permission to save promotions as '.$status.'.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCompanyId();
    }
}
