<?php

namespace Ispos\Backoffice\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

class BulkRoleMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['ulid', 'exists:users,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = Role::query()->find($this->input('role_id'));

            if (! $role) {
                return;
            }

            if ($role->name === 'Super Admin' && ! $this->user()?->isSuperAdmin()) {
                $validator->errors()->add('role_id', 'You cannot assign the Super Admin role.');
            }

            if ($role->name === 'Developer' && ! $this->user()?->isDeveloper()) {
                $validator->errors()->add('role_id', 'You cannot assign the Developer role.');
            }

            $userIds = $this->input('user_ids', []);

            if ($userIds === []) {
                return;
            }

            if (! $this->user()?->hasGlobalOrganizationAccess()) {
                $invalidUsers = User::query()
                    ->whereIn('id', $userIds)
                    ->where('company_id', '!=', $this->user()->company_id)
                    ->exists();

                if ($invalidUsers) {
                    $validator->errors()->add(
                        'user_ids',
                        'All selected users must belong to your company.',
                    );
                }
            }
        });
    }
}
