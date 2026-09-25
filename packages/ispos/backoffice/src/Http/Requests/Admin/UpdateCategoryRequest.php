<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Ispos\Backoffice\Http\Requests\Admin\Concerns\ValidatesCompanyScopedRequest;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    use ValidatesCompanyScopedRequest;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('category')) ?? false;
    }

    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');
        $companyId = $this->input('company_id', $category->company_id);

        return [
            'company_id' => ['required', 'ulid', 'exists:companies,id'],
            'parent_id' => ['nullable', 'ulid', 'exists:categories,id', Rule::notIn([$category->id])],
            'category_code' => ['required', 'string', 'max:50', Rule::unique('categories', 'category_code')->where(fn ($q) => $q->where('company_id', $companyId))->ignore($category->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeCompanyId();
    }
}
