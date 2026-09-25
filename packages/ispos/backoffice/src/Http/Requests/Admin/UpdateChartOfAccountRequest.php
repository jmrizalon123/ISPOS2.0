<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChartOfAccountRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest;

    public function authorize(): bool
    {
        return $this->user()?->can('accounting.post') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->route('chart_of_account')?->company_id ?? $this->user()?->company_id;

        return [
            'company_id' => ['required', 'ulid', 'exists:companies,id'],
            'account_code' => ['required', 'string', 'max:50', Rule::unique('chart_of_accounts', 'account_code')->where(fn ($q) => $q->where('company_id', $companyId))->ignore($this->route('chart_of_account'))],
            'account_name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', Rule::in(ChartOfAccount::TYPES)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCompanyId();
    }
}
