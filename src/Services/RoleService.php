<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Rabbanist\AdminDashboard\Models\Role;
use Rabbanist\AdminDashboard\Exceptions\AdminDashboardException;

class RoleService
{
    /**
     * Create a new role.
     */
    public function createRole(array $data): Role
    {
        Validator::make($data, [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:255', 'unique:admin_roles,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name'         => $data['name'],
                'slug'         => $data['slug'],
                'description'  => $data['description'] ?? null,
                'is_protected' => false,
            ]);

            if (!empty($data['privileges'])) {
                $this->attachPrivileges($role, $data['privileges']);
            }

            return $role;
        });
    }

    /**
     * Update an existing role.
     */
    public function updateRole(Role $role, array $data): Role
    {
        Validator::make($data, [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:255', 'unique:admin_roles,slug,' . $role->id],
            'description' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        return DB::transaction(function () use ($role, $data) {
            // Prevent changing slug of protected roles to prevent breaking system policies
            if ($role->isProtected() && $role->slug !== $data['slug']) {
                throw AdminDashboardException::invalidConfiguration(
                    'roles',
                    "The slug of a protected role [{$role->name}] cannot be modified."
                );
            }

            $role->update([
                'name'        => $data['name'],
                'slug'        => $data['slug'],
                'description' => $data['description'] ?? null,
            ]);

            if (isset($data['privileges'])) {
                $this->attachPrivileges($role, $data['privileges']);
            }

            return $role;
        });
    }

    /**
     * Sync privileges assigned to a role.
     */
    public function attachPrivileges(Role $role, array $privileges): void
    {
        DB::transaction(function () use ($role, $privileges) {
            $role->privileges()->sync($privileges);

            // Invalidate privileges cache for all users belonging to this role
            foreach ($role->users as $user) {
                if (method_exists($user, 'clearPrivilegesCache')) {
                    $user->clearPrivilegesCache();
                }
                if (method_exists($user, 'clearRolesCache')) {
                    $user->clearRolesCache();
                }
            }
        });
    }

    /**
     * Bulk assign users to a role.
     */
    public function syncUsers(Role $role, array $userIds): void
    {
        DB::transaction(function () use ($role, $userIds) {
            // Clear cache for users currently in the role
            foreach ($role->users as $user) {
                if (method_exists($user, 'clearRolesCache')) {
                    $user->clearRolesCache();
                }
                if (method_exists($user, 'clearPrivilegesCache')) {
                    $user->clearPrivilegesCache();
                }
            }

            $role->users()->sync($userIds);

            // Clear cache for newly added users
            $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
            $newUsers = $userModelClass::whereIn('id', $userIds)->get();

            foreach ($newUsers as $user) {
                if (method_exists($user, 'clearRolesCache')) {
                    $user->clearRolesCache();
                }
                if (method_exists($user, 'clearPrivilegesCache')) {
                    $user->clearPrivilegesCache();
                }
            }
        });
    }

    /**
     * Safe delete a role.
     */
    public function deleteRole(Role $role): void
    {
        if ($role->isProtected()) {
            throw AdminDashboardException::invalidConfiguration(
                'roles',
                "The role [{$role->name}] is protected and cannot be deleted."
            );
        }

        DB::transaction(function () use ($role) {
            // Clear caches before detaching users
            foreach ($role->users as $user) {
                if (method_exists($user, 'clearRolesCache')) {
                    $user->clearRolesCache();
                }
                if (method_exists($user, 'clearPrivilegesCache')) {
                    $user->clearPrivilegesCache();
                }
            }

            // Detach users and privileges first to cascade nicely
            $role->users()->detach();
            $role->privileges()->detach();
            $role->delete();
        });
    }

    /**
     * Get defaults for role seeding.
     */
    public function getDefaultRoles(): array
    {
        return [
            [
                'name'         => 'Super Admin',
                'slug'         => 'super-admin',
                'description'  => 'Full system control and unrestricted configuration.',
                'is_protected' => true,
            ],
            [
                'name'         => 'Admin',
                'slug'         => 'admin',
                'description'  => 'Dashboard access and standard management privileges.',
                'is_protected' => true,
            ],
            [
                'name'         => 'Editor',
                'slug'         => 'editor',
                'description'  => 'Manage core and content settings with no system configuration edits.',
                'is_protected' => false,
            ],
            [
                'name'         => 'Viewer',
                'slug'         => 'viewer',
                'description'  => 'Read-only view access across the dashboard panels.',
                'is_protected' => false,
            ],
        ];
    }
}
