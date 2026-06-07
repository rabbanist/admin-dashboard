<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Rabbanist\AdminDashboard\Models\Privilege;

/**
 * Add this trait to your User model to integrate with the admin dashboard
 * role/privilege system.
 *
 *     use HasAdminAccess;
 *
 * Required columns (added by the package migration):
 *   profile_photo_path, bio, phone, last_login_at, last_login_ip,
 *   suspended_at, suspension_reason
 */
trait HasAdminAccess
{
    use HasRoles, HasPrivileges;

    /**
     * Privileges assigned directly to this user (bypassing roles).
     *
     * Alias for privileges() defined in HasPrivileges trait.
     */
    public function directPrivileges(): BelongsToMany
    {
        return $this->privileges();
    }

    /**
     * Get all privileges for this user (from roles + direct assignments),
     * merged and de-duplicated.
     */
    public function getAllPrivileges(): Collection
    {
        $rolePrivileges = Privilege::whereHas('roles', function (Builder $query): void {
            $query->whereIn('admin_roles.id', $this->roles()->pluck('admin_roles.id'));
        })->get();

        return $rolePrivileges->merge($this->privileges)->unique('id')->values();
    }

    /**
     * Determine whether this user has any of the given roles.
     *
     * @param  string|array<string>  $roles  Role slug(s).
     */
    public function hasAnyRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign one or more roles to this user.
     *
     * @param  int|array<int>  $roleIds
     */
    public function assignRoles(int|array $roleIds): void
    {
        $this->roles()->syncWithoutDetaching($roleIds);
        $this->clearRolesCache();
    }

    /**
     * Remove one or more roles from this user.
     *
     * @param  int|array<int>  $roleIds
     */
    public function removeRoles(int|array $roleIds): void
    {
        $this->roles()->detach($roleIds);
        $this->clearRolesCache();
    }

    /**
     * Grant direct privilege(s) to this user.
     *
     * @param  int|array<int>  $privilegeIds
     */
    public function grantPrivileges(int|array $privilegeIds): void
    {
        $this->privileges()->syncWithoutDetaching($privilegeIds);
        $this->clearPrivilegesCache();
    }

    /**
     * Revoke direct privilege(s) from this user.
     *
     * @param  int|array<int>  $privilegeIds
     */
    public function revokePrivileges(int|array $privilegeIds): void
    {
        $this->privileges()->detach($privilegeIds);
        $this->clearPrivilegesCache();
    }

    /**
     * Determine whether this user has admin access.
     *
     * A user is an admin if they are a super-admin, have the "admin" role,
     * or have a truthy `is_admin` column.
     */
    public function isAdmin(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->hasRole('admin') || $this->hasRole('super-admin')) {
            return true;
        }

        if (isset($this->attributes['is_admin'])) {
            return (bool) $this->attributes['is_admin'];
        }

        return false;
    }

    /**
     * Determine whether this user is a super admin (by email whitelist).
     */
    public function isSuperAdmin(): bool
    {
        $superAdmins = config('admin-dashboard.authorization.super_admins', []);

        return in_array($this->email, $superAdmins, true);
    }

    /**
     * Determine whether this user is currently suspended.
     */
    public function isSuspended(): bool
    {
        return ! is_null($this->suspended_at);
    }

    /**
     * Suspend this user with an optional reason.
     */
    public function suspend(?string $reason = null): void
    {
        $this->update([
            'suspended_at'      => now(),
            'suspension_reason' => $reason,
        ]);
    }

    /**
     * Lift the suspension on this user.
     */
    public function unsuspend(): void
    {
        $this->update([
            'suspended_at'      => null,
            'suspension_reason' => null,
        ]);
    }

    /**
     * Record a login timestamp and IP address.
     */
    public function recordLogin(?string $ipAddress = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress ?? request()->ip(),
        ]);
    }

    /**
     * Scope to only admin users (by role).
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->whereHas('roles', function (Builder $q): void {
            $q->whereIn('slug', ['admin', 'super-admin']);
        });
    }

    /**
     * Scope to only suspended users.
     */
    public function scopeSuspended(Builder $query): Builder
    {
        return $query->whereNotNull('suspended_at');
    }

    /**
     * Scope to only active (non-suspended) users.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('suspended_at');
    }

    /**
     * Get the additional casts that should be merged into the model.
     *
     * Call `$this->mergeCasts($this->adminDashboardCasts())` in your
     * User model's constructor if you want automatic casting.
     *
     * @return array<string, string>
     */
    public function adminDashboardCasts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'suspended_at'  => 'datetime',
        ];
    }
}
