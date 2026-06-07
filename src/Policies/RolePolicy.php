<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Rabbanist\AdminDashboard\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(Authenticatable $user): bool
    {
        return $this->checkPrivilege($user, 'view.roles');
    }

    /**
     * Determine whether the user can view a specific role.
     */
    public function view(Authenticatable $user, Role $role): bool
    {
        return $this->checkPrivilege($user, 'view.roles');
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(Authenticatable $user): bool
    {
        return $this->checkPrivilege($user, 'create.roles');
    }

    /**
     * Determine whether the user can update a role.
     */
    public function update(Authenticatable $user, Role $role): bool
    {
        // Protected roles can only be updated by super-admins.
        if ($role->isProtected() && ! $this->isSuperAdmin($user)) {
            return false;
        }

        return $this->checkPrivilege($user, 'update.roles');
    }

    /**
     * Determine whether the user can delete a role.
     */
    public function delete(Authenticatable $user, Role $role): bool
    {
        // Protected roles cannot be deleted by anyone.
        if ($role->isProtected()) {
            return false;
        }

        return $this->checkPrivilege($user, 'delete.roles');
    }

    /**
     * Check if the user has a specific privilege.
     */
    protected function checkPrivilege(Authenticatable $user, string $privilege): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if (method_exists($user, 'hasPrivilege')) {
            return $user->hasPrivilege($privilege);
        }

        return false;
    }

    /**
     * Check if the user is a super admin.
     */
    protected function isSuperAdmin(Authenticatable $user): bool
    {
        if (method_exists($user, 'isSuperAdmin')) {
            return $user->isSuperAdmin();
        }

        $superAdmins = config('admin-dashboard.authorization.super_admins', []);

        return in_array($user->email, $superAdmins, true);
    }
}
