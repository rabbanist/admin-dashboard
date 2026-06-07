<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin can view a list of users.
     */
    public function viewAny(Authenticatable $admin): bool
    {
        return $this->isAdmin($admin);
    }

    /**
     * Determine whether the admin can view a specific user.
     */
    public function view(Authenticatable $admin, Authenticatable $user): bool
    {
        return $this->isAdmin($admin);
    }

    /**
     * Determine whether the admin can create users.
     */
    public function create(Authenticatable $admin): bool
    {
        return $this->isAdmin($admin);
    }

    /**
     * Determine whether the admin can update a user.
     */
    public function update(Authenticatable $admin, Authenticatable $user): bool
    {
        return $this->isAdmin($admin);
    }

    /**
     * Determine whether the admin can delete a user.
     */
    public function delete(Authenticatable $admin, Authenticatable $user): bool
    {
        // Prevent self-deletion.
        if ($admin->getKey() === $user->getKey()) {
            return false;
        }

        return $this->isAdmin($admin);
    }

    /**
     * Determine whether the admin can restore a soft-deleted user.
     */
    public function restore(Authenticatable $admin, Authenticatable $user): bool
    {
        return $this->isAdmin($admin);
    }

    /**
     * Determine whether the admin can permanently delete a user.
     */
    public function forceDelete(Authenticatable $admin, Authenticatable $user): bool
    {
        $superAdmins = config('admin-dashboard.authorization.super_admins', []);

        return in_array($admin->email, $superAdmins, true);
    }

    /**
     * Determine whether the admin can impersonate a user.
     */
    public function impersonate(Authenticatable $admin, Authenticatable $user): bool
    {
        if (! config('admin-dashboard.features.user_impersonation', false)) {
            return false;
        }

        // Cannot impersonate yourself.
        if ($admin->getKey() === $user->getKey()) {
            return false;
        }

        return $this->isAdmin($admin);
    }

    /**
     * Check if the given user qualifies as an admin.
     */
    protected function isAdmin(Authenticatable $user): bool
    {
        $superAdmins = config('admin-dashboard.authorization.super_admins', []);

        if (in_array($user->email, $superAdmins, true)) {
            return true;
        }

        if (method_exists($user, 'isAdmin')) {
            return (bool) $user->isAdmin();
        }

        return false;
    }
}
