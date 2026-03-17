<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'manage users',
            'manage roles',
            'manage permissions',
            'manage brands',
            'manage categories',
            'manage vehicles',
            'manage stock',
            'manage purchases',
            'manage sales',
            'manage business settings',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            'super-admin' => $permissions,
            'manager' => [
                'view dashboard',
                'manage brands',
                'manage categories',
                'manage vehicles',
                'manage stock',
                'manage purchases',
                'manage sales',
                'manage business settings',
            ],
            'purchase-operator' => [
                'view dashboard',
                'manage brands',
                'manage categories',
                'manage vehicles',
                'manage stock',
                'manage purchases',
            ],
            'sales-operator' => [
                'view dashboard',
                'manage vehicles',
                'manage stock',
                'manage sales',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
