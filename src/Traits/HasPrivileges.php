<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use Yourvendor\AdminDashboard\Models\Privilege;

trait HasPrivileges
{
    /**
     * Privileges assigned directly to this user (bypassing roles).
     */
    public function privileges(): BelongsToMany
    {
        return $this->belongsToMany(
            Privilege::class,
            'admin_privilege_user',
            'user_id',
            'privilege_id',
        )->withPivot('assigned_at');
    }

    /**
     * Grant a direct privilege to the user.
     *
     * @param  Privilege|string|int  $privilege
     */
    public function givePrivilege(Privilege|string|int $privilege): void
    {
        $id = $this->resolvePrivilegeId($privilege);
        $this->privileges()->syncWithoutDetaching([$id]);
        $this->clearPrivilegesCache();
    }

    /**
     * Revoke a direct privilege from the user.
     *
     * @param  Privilege|string|int  $privilege
     */
    public function revokePrivilege(Privilege|string|int $privilege): void
    {
        $id = $this->resolvePrivilegeId($privilege);
        $this->privileges()->detach($id);
        $this->clearPrivilegesCache();
    }

    /**
     * Determine whether the user has the specified privilege (directly or through roles).
     *
     * @param  Privilege|string  $privilege
     */
    public function hasPrivilege(Privilege|string $privilege): bool
    {
        // Super-admins bypass privilege checks.
        if (method_exists($this, 'isSuperAdmin') && $this->isSuperAdmin()) {
            return true;
        }

        $slug = $privilege instanceof Privilege ? $privilege->slug : $privilege;

        return in_array($slug, $this->getCachedPrivilegeSlugs(), true);
    }

    /**
     * Retrieve cached privilege slugs for the user (both direct and inherited).
     */
    public function getCachedPrivilegeSlugs(): array
    {
        $userId = $this->getKey();

        if (! $userId) {
            return [];
        }

        return Cache::remember(
            "admin-dashboard:user:{$userId}:privilege_slugs",
            config('admin-dashboard.cache.ttl', 3600),
            function () {
                // Direct privileges
                $direct = $this->privileges()->pluck('slug')->toArray();

                // Inherited privileges from roles
                $inherited = [];
                if (method_exists($this, 'roles')) {
                    $inherited = $this->roles()
                        ->with('privileges')
                        ->get()
                        ->flatMap(fn ($role) => $role->privileges->pluck('slug'))
                        ->toArray();
                }

                return array_values(array_unique(array_merge($direct, $inherited)));
            }
        );
    }

    /**
     * Clear the privilege check cache for the user.
     */
    public function clearPrivilegesCache(): void
    {
        $userId = $this->getKey();

        if ($userId) {
            Cache::forget("admin-dashboard:user:{$userId}:privilege_slugs");
        }
    }

    /**
     * Resolve a privilege ID from a Privilege instance, ID, or slug.
     *
     * @param  Privilege|string|int  $privilege
     */
    protected function resolvePrivilegeId(Privilege|string|int $privilege): int
    {
        if ($privilege instanceof Privilege) {
            return $privilege->id;
        }

        if (is_int($privilege)) {
            return $privilege;
        }

        if (is_string($privilege)) {
            $resolved = Privilege::where('slug', $privilege)->first();

            if (! $resolved) {
                throw new \InvalidArgumentException("Privilege with slug [{$privilege}] not found.");
            }

            return $resolved->id;
        }

        throw new \InvalidArgumentException("Invalid privilege identifier provided.");
    }
}
