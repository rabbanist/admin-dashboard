<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Yourvendor\AdminDashboard\Models\Role;

trait HasRoles
{
    /**
     * The admin roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'admin_role_user',
            'user_id',
            'role_id',
        )->withPivot('assigned_at');
    }

    /**
     * Attach a role to the user.
     *
     * @param  Role|string|int  $role
     */
    public function attachRole(Role|string|int $role): void
    {
        $id = $this->resolveRoleId($role);
        $this->roles()->syncWithoutDetaching([$id]);
        $this->clearRolesCache();
    }

    /**
     * Detach a role from the user.
     *
     * @param  Role|string|int  $role
     */
    public function detachRole(Role|string|int $role): void
    {
        $id = $this->resolveRoleId($role);
        $this->roles()->detach($id);
        $this->clearRolesCache();
    }

    /**
     * Sync roles for the user.
     *
     * @param  array|Collection  $roles
     */
    public function syncRoles(array|Collection $roles): array
    {
        $ids = collect($roles)->map(fn ($r) => $this->resolveRoleId($r))->toArray();
        $changes = $this->roles()->sync($ids);
        $this->clearRolesCache();

        return $changes;
    }

    /**
     * Determine whether the user has the specified role.
     *
     * @param  Role|string  $role
     */
    public function hasRole(Role|string $role): bool
    {
        $slug = $role instanceof Role ? $role->slug : $role;

        return in_array($slug, $this->getCachedRoleSlugs(), true);
    }

    /**
     * Retrieve cached role slugs for the user.
     */
    public function getCachedRoleSlugs(): array
    {
        $userId = $this->getKey();

        if (! $userId) {
            return [];
        }

        return Cache::remember(
            "admin-dashboard:user:{$userId}:role_slugs",
            config('admin-dashboard.cache.ttl', 3600),
            fn () => $this->roles()->pluck('slug')->toArray()
        );
    }

    /**
     * Clear the role check cache for the user.
     */
    public function clearRolesCache(): void
    {
        $userId = $this->getKey();

        if ($userId) {
            Cache::forget("admin-dashboard:user:{$userId}:role_slugs");

            // Also clear privileges cache because role changes alter inherited privileges
            if (method_exists($this, 'clearPrivilegesCache')) {
                $this->clearPrivilegesCache();
            }
        }
    }

    /**
     * Resolve a role ID from a Role instance, ID, or slug.
     *
     * @param  Role|string|int  $role
     */
    protected function resolveRoleId(Role|string|int $role): int
    {
        if ($role instanceof Role) {
            return $role->id;
        }

        if (is_int($role)) {
            return $role;
        }

        if (is_string($role)) {
            $resolved = Role::where('slug', $role)->first();

            if (! $resolved) {
                throw new \InvalidArgumentException("Role with slug [{$role}] not found.");
            }

            return $resolved->id;
        }

        throw new \InvalidArgumentException("Invalid role identifier provided.");
    }
}
