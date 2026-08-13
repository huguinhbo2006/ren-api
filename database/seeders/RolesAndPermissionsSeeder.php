<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            'access-reports',
            'export-pdf',
            'export-excel',
            'manage-users',
            'manage-settings',
            'manage-rentals',
            'manage-assets',
            'manage-customers',
            'manage-expenses',
            'manage-payments',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Roles
        // 1. Admin
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        // 2. User (regular tenant / owner)
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $userRole->syncPermissions([
            'access-reports',
            'export-pdf',
            'manage-settings',
            'manage-rentals',
            'manage-assets',
            'manage-customers',
            'manage-expenses',
            'manage-payments',
        ]);

        // 3. Viewer (read-only collaborator)
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerRole->syncPermissions([
            'manage-rentals',
            'manage-assets',
            'manage-customers',
        ]);
    }
}
