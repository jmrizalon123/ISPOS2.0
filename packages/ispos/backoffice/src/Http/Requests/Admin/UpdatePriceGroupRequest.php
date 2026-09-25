<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use App\Models\PriceGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePriceGroupRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('price_group')) ?? false;
    }

    public function rules(): array
    {
        /** @var PriceGroup $priceGroup */
        $priceGroup = $this->route('price_group');
        $companyId = $this->input('company_id', $priceGroup->company_id);

        return [
            'company_id' => ['required', 'ulid', 'exists:companies,id'],
            'group_code' => ['required', 'string', 'max:50', Rule::unique('price_groups', 'group_code')->where(fn ($q) => $q->where('company_id', $companyId))->ignore($priceGroup->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_default' => ['boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCompanyId();
        $this->merge(['is_default' => $this->boolean('is_default')]);
    }
}
