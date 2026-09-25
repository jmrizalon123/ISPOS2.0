<?php

namespace Ispos\Backoffice\Http\Requests\Admin\Concerns;

use App\Domains\Identity\Services\UserAvatarService;
use Illuminate\Validation\Rule;

trait ValidatesUserProfileFields
{
    /** @return array<string, mixed> */
    protected function userProfileRules(?string $userId = null): array
    {
        return [
            'employee_id' => ['nullable', 'string', 'max:50'],
            'username' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.UserAvatarService::MAX_KILOBYTES],
            'remove_avatar' => ['boolean'],
            'name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'ulid', 'exists:companies,id'],
            'department_id' => ['nullable', 'ulid', 'exists:departments,id'],
            'position_id' => ['nullable', 'ulid', 'exists:positions,id'],
            'base_type' => ['required', Rule::in(['head_office', 'store', 'warehouse'])],
            'default_store_id' => ['nullable', 'ulid', 'exists:stores,id'],
            'default_warehouse_id' => ['nullable', 'ulid', 'exists:stores,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_active' => ['boolean'],
            'is_locked' => ['boolean'],
            'language' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'store_ids' => ['array'],
            'store_ids.*' => ['ulid', 'exists:stores,id'],
        ];
    }
}
