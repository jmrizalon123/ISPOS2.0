<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('unit')) ?? false;
    }

    public function rules(): array
    {
        /** @var Unit $unit */
        $unit = $this->route('unit');
        $companyId = $this->input('company_id', $unit->company_id);

        return [
            'company_id' => ['required', 'ulid', 'exists:companies,id'],
            'unit_code' => ['required', 'string', 'max:50', Rule::unique('units', 'unit_code')->where(fn ($q) => $q->where('company_id', $companyId))->ignore($unit->id)],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCompanyId();
    }
}
