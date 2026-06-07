<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Yourvendor\AdminDashboard\Models\Privilege;

class PrivilegePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any privileges.
     */
    public function viewAny(Authenticatable $user): bool
    {
        return $this->checkPrivilege($user, 'view.roles');
    }

    /**
     * Determine whether the user can view a specific privilege.
     */
    public function view(Authenticatable $user, Privilege $privilege): bool
    {
        return $this->checkPrivilege($user, 'view.roles');
    }

    /**
     * Only super-admins can create privileges (system-level operation).
     */
    public function create(Authenticatable $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Only super-admins can update privileges.
     */
    public function update(Authenticatable $user, Privilege $privilege): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Only super-admins can delete privileges.
     */
    public function delete(Authenticatable $user, Privilege $privilege): bool
    {
        return $this->isSuperAdmin($user);
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
