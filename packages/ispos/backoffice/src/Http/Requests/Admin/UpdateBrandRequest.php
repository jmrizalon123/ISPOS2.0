<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('brand')) ?? false;
    }

    public function rules(): array
    {
        /** @var Brand $brand */
        $brand = $this->route('brand');
        $companyId = $this->input('company_id', $brand->company_id);

        return [
            'company_id' => ['required', 'ulid', 'exists:companies,id'],
            'brand_code' => ['required', 'string', 'max:50', Rule::unique('brands', 'brand_code')->where(fn ($q) => $q->where('company_id', $companyId))->ignore($brand->id)],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCompanyId();
    }
}
