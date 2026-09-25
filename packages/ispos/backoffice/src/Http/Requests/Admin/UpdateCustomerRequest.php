<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customers.update') ?? false;
    }

    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'customer_code' => ['required', 'string', 'max:50', Rule::unique('customers', 'customer_code')->where(fn ($q) => $q->where('company_id', $customer->company_id))->ignore($customer->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'price_group_id' => ['nullable', 'ulid', 'exists:price_groups,id'],
            'loyalty_program_id' => ['nullable', 'ulid', 'exists:loyalty_programs,id'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
