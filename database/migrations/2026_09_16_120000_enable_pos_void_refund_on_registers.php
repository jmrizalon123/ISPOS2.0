<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getSchemaBuilder()->hasColumn('registers', 'allow_void')) {
            DB::table('registers')->update(['allow_void' => true]);
        }

        if (DB::getSchemaBuilder()->hasColumn('registers', 'allow_refund')) {
            DB::table('registers')->update(['allow_refund' => true]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['pos.void', 'pos.refund'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach ([
            'Cashier' => ['pos.void', 'pos.refund'],
            'Supervisor' => ['pos.void'],
            'Branch Manager' => ['pos.void', 'pos.refund'],
        ] as $roleName => $permissions) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive: keep void/refund enabled once turned on.
    }
};
