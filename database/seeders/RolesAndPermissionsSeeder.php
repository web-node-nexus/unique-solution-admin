<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Module => actions map.
     *
     * @var array<string, list<string>>
     */
    private array $modules = [
        'dashboard' => ['view'],
        'categories' => ['view', 'create', 'update', 'delete'],
        'banners' => ['view', 'create', 'update', 'delete'],
        'brands' => ['view', 'create', 'update', 'delete'],
        'attributes' => ['view', 'create', 'update', 'delete'],
        'products' => ['view', 'create', 'update', 'delete', 'export'],
        'inventory' => ['view', 'create', 'update', 'delete', 'export'],
        'orders' => ['view', 'create', 'update', 'delete', 'export', 'approve'],
        'payments' => ['view', 'create', 'update', 'delete', 'export'],
        'refunds' => ['view', 'create', 'update', 'delete', 'approve'],
        'customers' => ['view', 'create', 'update', 'delete', 'export'],
        'staff' => ['view', 'create', 'update', 'delete'],
        'coupons' => ['view', 'create', 'update', 'delete'],
        'sales' => ['view', 'create', 'update', 'delete'],
        'notifications' => ['view', 'create', 'update', 'delete'],
        'reviews' => ['view', 'create', 'update', 'delete', 'approve'],
        'reports' => ['view', 'export'],
        'settings' => ['view', 'update'],
        'activity-logs' => ['view', 'export'],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissionNames = [];

        foreach ($this->modules as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";
                Permission::query()->firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web']
                );
                $allPermissionNames[] = $name;
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdmin = Role::findOrCreate('Super Admin', 'web');
        $superAdmin->syncPermissions($allPermissionNames);

        $manager = Role::findOrCreate('Manager', 'web');
        $managerPermissions = array_values(array_filter(
            $allPermissionNames,
            fn (string $permission): bool => $permission !== 'staff.delete'
        ));
        $manager->syncPermissions($managerPermissions);

        $staff = Role::findOrCreate('Staff', 'web');
        $staff->syncPermissions([
            'dashboard.view',
            'categories.view',
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'products.export',
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.delete',
            'inventory.export',
            'orders.view',
            'orders.update',
        ]);

        $support = Role::findOrCreate('Support', 'web');
        $support->syncPermissions([
            'dashboard.view',
            'orders.view',
            'orders.update',
            'customers.view',
            'refunds.view',
            'refunds.update',
            'refunds.approve',
            'reviews.view',
            'reviews.create',
            'reviews.update',
            'reviews.delete',
            'reviews.approve',
        ]);
    }
}
