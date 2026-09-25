<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Phase 1
            'dashboard.view',
            'companies.view',
            'companies.create',
            'companies.update',
            'companies.delete',
            'stores.view',
            'stores.create',
            'stores.update',
            'stores.delete',
            'registers.view',
            'registers.create',
            'registers.update',
            'registers.delete',
            'sales_plans.view',
            'sales_plans.create',
            'sales_plans.update',
            'sales_plans.delete',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'settings.view',
            'settings.update',
            'audit.view',
            // Phase 2 — Catalog
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'brands.view',
            'brands.create',
            'brands.update',
            'brands.delete',
            'units.view',
            'units.create',
            'units.update',
            'units.delete',
            'taxes.view',
            'taxes.create',
            'taxes.update',
            'taxes.delete',
            'price_groups.view',
            'price_groups.create',
            'price_groups.update',
            'price_groups.delete',
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            // Reserved for later phases
            'inventory.view',
            'inventory.adjust',
            'purchasing.view',
            'purchasing.create',
            'purchasing.approve',
            'pos.access',
            'pos.refund',
            'pos.void',
            'customers.view',
            'customers.create',
            'customers.update',
            'loyalty.view',
            'loyalty.manage',
            'memberships.view',
            'memberships.manage',
            'promotions.view',
            'promotions.create',
            'promotions.update',
            'promotions.draft',
            'promotions.active',
            'promotions.expired',
            'promotions.cancelled',
            'online_store.view',
            'online_store.manage',
            'online_store.manage_orders',
            'reports.view',
            'reports.export',
            'accounting.view',
            'accounting.post',
            'ap.view',
            'ap.post',
            'ap.pay',
            'kds.view',
            'sync.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $all = Permission::all();
        $platformCreatePermissions = ['companies.create', 'stores.create'];
        $superAdminPermissions = $all->pluck('name')
            ->reject(fn (string $permission) => in_array($permission, $platformCreatePermissions, true))
            ->values()
            ->all();

        $roleMap = [
            'Developer' => [
                'dashboard.view',
                'companies.view', 'companies.create', 'companies.update', 'companies.delete',
                'stores.view', 'stores.create', 'stores.update', 'stores.delete',
                'registers.view', 'registers.create', 'registers.update', 'registers.delete',
                'sales_plans.view', 'sales_plans.create', 'sales_plans.update', 'sales_plans.delete',
                'users.view', 'users.create', 'users.update',
                'roles.view', 'roles.update',
                'settings.view', 'settings.update', 'audit.view', 'sync.manage',
                'categories.view', 'categories.create', 'categories.update', 'categories.delete',
                'brands.view', 'brands.create', 'brands.update', 'brands.delete',
                'units.view', 'units.create', 'units.update', 'units.delete',
                'taxes.view', 'taxes.create', 'taxes.update', 'taxes.delete',
                'price_groups.view', 'price_groups.create', 'price_groups.update', 'price_groups.delete',
                'products.view', 'products.create', 'products.update', 'products.delete',
            ],
            'Super Admin' => $superAdminPermissions,
            'Company Admin' => [
                'dashboard.view', 'stores.view', 'stores.update', 'stores.delete',
                'registers.view', 'registers.create', 'registers.update', 'registers.delete',
                'sales_plans.view', 'sales_plans.create', 'sales_plans.update', 'sales_plans.delete',
                'users.view', 'users.create', 'users.update', 'users.delete',
                'roles.view', 'roles.create', 'roles.update',
                'settings.view', 'settings.update', 'audit.view',
                'categories.view', 'categories.create', 'categories.update', 'categories.delete',
                'brands.view', 'brands.create', 'brands.update', 'brands.delete',
                'units.view', 'units.create', 'units.update', 'units.delete',
                'taxes.view', 'taxes.create', 'taxes.update', 'taxes.delete',
                'price_groups.view', 'price_groups.create', 'price_groups.update', 'price_groups.delete',
                'products.view', 'products.create', 'products.update', 'products.delete',
                'inventory.view', 'inventory.adjust', 'purchasing.view', 'purchasing.create', 'purchasing.approve',
                'pos.access', 'customers.view', 'customers.create', 'customers.update',
                'loyalty.view', 'loyalty.manage', 'memberships.view', 'memberships.manage',
                'promotions.view', 'promotions.create', 'promotions.update',
                'promotions.draft', 'promotions.active', 'promotions.expired', 'promotions.cancelled',
                'online_store.view', 'online_store.manage', 'online_store.manage_orders',
                'reports.view', 'reports.export', 'accounting.view', 'accounting.post',
                'ap.view', 'ap.post', 'ap.pay', 'sync.manage',
            ],
            'Branch Manager' => [
                'dashboard.view', 'stores.view', 'stores.update',
                'registers.view', 'registers.create', 'registers.update',
                'sales_plans.view',
                'users.view', 'users.create', 'users.update',
                'settings.view', 'audit.view',
                'categories.view', 'brands.view', 'units.view', 'taxes.view', 'price_groups.view',
                'products.view', 'products.create', 'products.update',
                'inventory.view', 'inventory.adjust',
                'purchasing.view', 'purchasing.create', 'pos.access', 'pos.refund', 'pos.void',
                'customers.view', 'loyalty.view', 'memberships.view', 'promotions.view',
                'online_store.view', 'online_store.manage', 'online_store.manage_orders',
                'reports.view', 'reports.export',
            ],
            'Store Manager' => [
                'dashboard.view', 'stores.view', 'stores.update',
                'registers.view', 'registers.update',
                'sales_plans.view',
                'users.view', 'settings.view',
                'categories.view', 'brands.view', 'units.view', 'taxes.view', 'price_groups.view',
                'products.view', 'products.create', 'products.update',
                'inventory.view', 'inventory.adjust',
                'purchasing.view', 'pos.access', 'pos.refund', 'pos.void',
                'customers.view', 'customers.create', 'customers.update',
                'loyalty.view', 'memberships.view',
                'promotions.view', 'promotions.create', 'promotions.update',
                'promotions.draft', 'promotions.active',
                'online_store.view', 'online_store.manage', 'online_store.manage_orders',
                'reports.view',
            ],
            'Supervisor' => [
                'dashboard.view', 'registers.view', 'users.view',
                'products.view', 'inventory.view', 'pos.access', 'pos.refund', 'pos.void',
                'customers.view', 'reports.view',
            ],
            'Cashier' => [
                'dashboard.view', 'pos.access', 'pos.refund', 'pos.void',
                'products.view', 'customers.view', 'customers.create',
            ],
            'Inventory Clerk' => [
                'dashboard.view',
                'categories.view', 'categories.create', 'categories.update',
                'brands.view', 'brands.create', 'brands.update',
                'units.view', 'units.create', 'units.update',
                'taxes.view',
                'price_groups.view',
                'products.view', 'products.create', 'products.update',
                'inventory.view', 'inventory.adjust', 'purchasing.view',
            ],
            'Purchasing Officer' => [
                'dashboard.view', 'products.view', 'inventory.view',
                'purchasing.view', 'purchasing.create', 'purchasing.approve',
            ],
            'Accountant' => [
                'dashboard.view', 'reports.view', 'reports.export', 'accounting.view', 'accounting.post',
                'ap.view', 'ap.post', 'ap.pay', 'audit.view',
            ],
            'Auditor' => [
                'dashboard.view', 'audit.view', 'reports.view', 'reports.export', 'accounting.view', 'ap.view',
            ],
            'Report Viewer' => [
                'dashboard.view', 'reports.view',
            ],
            'Kitchen Staff' => [
                'kds.view',
            ],
        ];

        foreach ($roleMap as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($perms);
        }
    }
}
