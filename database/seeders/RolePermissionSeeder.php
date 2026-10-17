<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permission matrix per Tech Spec §7. Owner bypasses all checks via
 * Gate::before (AppServiceProvider), so it isn't assigned every permission
 * explicitly, only the ones relevant for role-management UI.
 */
class RolePermissionSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    public const MATRIX = [
        'dashboard.view' => ['Owner', 'Admin', 'Gudang'],
        'pos.access' => ['Owner', 'Admin', 'Kasir'],
        'pos.override-price' => ['Owner', 'Admin'],
        'pos.discount-unlimited' => ['Owner', 'Admin'],
        'pos.credit-sale' => ['Owner', 'Admin', 'Kasir'],
        'sales.view' => ['Owner', 'Admin', 'Kasir'],
        'sales.void' => ['Owner', 'Admin'],
        'sales.return' => ['Owner', 'Admin', 'Kasir'],
        'products.view' => ['Owner', 'Admin', 'Kasir', 'Gudang'],
        'products.manage' => ['Owner', 'Admin'],
        'products.view-cost' => ['Owner', 'Admin'],
        'customers.manage' => ['Owner', 'Admin', 'Kasir'],
        'suppliers.manage' => ['Owner', 'Admin', 'Gudang'],
        'purchases.manage' => ['Owner', 'Admin', 'Gudang'],
        'purchases.void' => ['Owner', 'Admin'],
        'stock.view' => ['Owner', 'Admin', 'Kasir', 'Gudang'],
        'stock.opname' => ['Owner', 'Admin', 'Gudang'],
        'stock.adjust' => ['Owner', 'Admin', 'Gudang'],
        'stock.transfer' => ['Owner', 'Admin', 'Gudang'],
        'cash.manage' => ['Owner', 'Admin', 'Kasir'],
        'receivables.manage' => ['Owner', 'Admin', 'Kasir'],
        'payables.manage' => ['Owner', 'Admin'],
        'reports.view' => ['Owner', 'Admin', 'Gudang'],
        'settings.manage' => ['Owner'],
        'users.manage' => ['Owner'],
    ];

    public function run(): void
    {
        Cache::forget('spatie.permission.cache');

        foreach (array_keys(self::MATRIX) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = ['Owner', 'Admin', 'Kasir', 'Gudang'];

        foreach ($roles as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');

            $permissions = collect(self::MATRIX)
                ->filter(fn (array $roles) => in_array($roleName, $roles, true))
                ->keys()
                ->all();

            $role->syncPermissions($permissions);
        }
    }
}
