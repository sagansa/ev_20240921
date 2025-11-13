<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the application's baseline roles and permissions.
     */
    public function run(): void
    {
        $permissions = [
            'dashboard.view',
            'vehicles.view',
            'vehicles.manage',
            'charging-sessions.view',
            'charging-sessions.manage',
            'charging-locations.view',
            'charging-locations.manage',
            'analytics.view',
            'advertisements.manage',
            'contributors.manage',
            'community-submissions.review',
            'reports.review',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        $rolePermissions = [
            'user' => [
                'dashboard.view',
                'vehicles.view',
                'charging-sessions.view',
                'charging-locations.view',
            ],
            'admin' => [
                'dashboard.view',
                'vehicles.view',
                'vehicles.manage',
                'charging-sessions.view',
                'charging-sessions.manage',
                'charging-locations.view',
                'charging-locations.manage',
                'analytics.view',
                'advertisements.manage',
                'contributors.manage',
                'community-submissions.review',
                'reports.review',
            ],
            'super_admin' => $permissions,
        ];

        foreach ($rolePermissions as $roleName => $permissionsForRole) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );

            $role->syncPermissions($permissionsForRole);
        }
    }
}
