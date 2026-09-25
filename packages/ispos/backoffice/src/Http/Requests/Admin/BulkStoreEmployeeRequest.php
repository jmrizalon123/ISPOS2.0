<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'ulid', 'exists:stores,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['ulid', 'exists:users,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $store = Store::query()->find($this->input('store_id'));

            if (! $store) {
                return;
            }

            $userIds = $this->input('user_ids', []);

            if ($userIds === []) {
                return;
            }

            $invalidUsers = User::query()
                ->whereIn('id', $userIds)
                ->where(function ($query) use ($store) {
                    $query->whereNull('company_id')
                        ->orWhere('company_id', '!=', $store->company_id);
                })
                ->exists();

            if ($invalidUsers) {
                $validator->errors()->add(
                    'user_ids',
                    'All selected users must belong to the same company as the store.',
                );
            }
        });
    }
}
