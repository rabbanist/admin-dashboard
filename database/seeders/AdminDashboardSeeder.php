<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Database\Seeders;

use Illuminate\Database\Seeder;
use Rabbanist\AdminDashboard\Models\Privilege;
use Rabbanist\AdminDashboard\Models\Role;

class AdminDashboardSeeder extends Seeder
{
    /**
     * Seed the default roles and privileges.
     */
    public function run(): void
    {
        $this->seedPrivileges();
        $this->seedRoles();
        $this->assignPrivilegesToRoles();
    }

    protected function seedPrivileges(): void
    {
        $privileges = [
            // Users
            ['name' => 'View Users',   'slug' => 'view.users',   'resource_type' => 'users',    'module' => 'core'],
            ['name' => 'Create Users', 'slug' => 'create.users', 'resource_type' => 'users',    'module' => 'core'],
            ['name' => 'Update Users', 'slug' => 'update.users', 'resource_type' => 'users',    'module' => 'core'],
            ['name' => 'Delete Users', 'slug' => 'delete.users', 'resource_type' => 'users',    'module' => 'core'],

            // Roles
            ['name' => 'View Roles',   'slug' => 'view.roles',   'resource_type' => 'roles',    'module' => 'core'],
            ['name' => 'Create Roles', 'slug' => 'create.roles', 'resource_type' => 'roles',    'module' => 'core'],
            ['name' => 'Update Roles', 'slug' => 'update.roles', 'resource_type' => 'roles',    'module' => 'core'],
            ['name' => 'Delete Roles', 'slug' => 'delete.roles', 'resource_type' => 'roles',    'module' => 'core'],

            // Audit Logs
            ['name' => 'View Audit Logs',  'slug' => 'view.audit-logs',  'resource_type' => 'audit_logs', 'module' => 'system'],
            ['name' => 'Export Audit Logs', 'slug' => 'export.audit-logs','resource_type' => 'audit_logs', 'module' => 'system'],

            // Settings
            ['name' => 'View Settings',   'slug' => 'view.settings',   'resource_type' => 'settings', 'module' => 'system'],
            ['name' => 'Update Settings', 'slug' => 'update.settings', 'resource_type' => 'settings', 'module' => 'system'],

            // File Manager
            ['name' => 'View Files',   'slug' => 'view.files',   'resource_type' => 'files', 'module' => 'content'],
            ['name' => 'Upload Files', 'slug' => 'upload.files', 'resource_type' => 'files', 'module' => 'content'],
            ['name' => 'Delete Files', 'slug' => 'delete.files', 'resource_type' => 'files', 'module' => 'content'],
        ];

        foreach ($privileges as $privilege) {
            Privilege::firstOrCreate(
                ['slug' => $privilege['slug']],
                $privilege,
            );
        }
    }

    protected function seedRoles(): void
    {
        $roles = [
            [
                'name'         => 'Super Admin',
                'slug'         => 'super-admin',
                'description'  => 'Unrestricted access to all system features.',
                'is_protected' => true,
            ],
            [
                'name'         => 'Admin',
                'slug'         => 'admin',
                'description'  => 'Full administrative access with some restrictions.',
                'is_protected' => true,
            ],
            [
                'name'         => 'Editor',
                'slug'         => 'editor',
                'description'  => 'Can manage content and view users.',
                'is_protected' => false,
            ],
            [
                'name'         => 'Viewer',
                'slug'         => 'viewer',
                'description'  => 'Read-only access to the dashboard.',
                'is_protected' => false,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['slug' => $role['slug']],
                $role,
            );
        }
    }

    protected function assignPrivilegesToRoles(): void
    {
        // Super Admin → all privileges.
        $superAdmin = Role::where('slug', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->privileges()->sync(
                Privilege::pluck('id')->toArray()
            );
        }

        // Admin → all privileges except role deletion.
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $admin->privileges()->sync(
                Privilege::where('slug', '!=', 'delete.roles')->pluck('id')->toArray()
            );
        }

        // Editor → view/create/update users + content privileges.
        $editor = Role::where('slug', 'editor')->first();
        if ($editor) {
            $editor->privileges()->sync(
                Privilege::whereIn('slug', [
                    'view.users', 'create.users', 'update.users',
                    'view.files', 'upload.files',
                ])->pluck('id')->toArray()
            );
        }

        // Viewer → view-only privileges.
        $viewer = Role::where('slug', 'viewer')->first();
        if ($viewer) {
            $viewer->privileges()->sync(
                Privilege::where('slug', 'like', 'view.%')->pluck('id')->toArray()
            );
        }
    }
}
