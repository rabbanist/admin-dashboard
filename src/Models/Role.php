<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rabbanist\AdminDashboard\Database\Factories\RoleFactory;
use Rabbanist\AdminDashboard\Exceptions\AdminDashboardException;

class Role extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'admin_roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_protected',
    ];

    protected function casts(): array
    {
        return [
            'is_protected' => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────

    /**
     * The privileges that belong to this role.
     */
    public function privileges(): BelongsToMany
    {
        return $this->belongsToMany(
            Privilege::class,
            'admin_role_privilege',
            'role_id',
            'privilege_id',
        );
    }

    /**
     * The users assigned to this role.
     */
    public function users(): BelongsToMany
    {
        $userModel = config('admin-dashboard.user_model', \App\Models\User::class);

        return $this->belongsToMany(
            $userModel,
            'admin_role_user',
            'role_id',
            'user_id',
        )->withPivot('assigned_at');
    }

    // ─── Methods ─────────────────────────────────────────────────────

    /**
     * Determine whether this role is protected from deletion.
     *
     * Protected roles are built-in system roles (e.g., "super-admin",
     * "admin") that must not be removed.
     */
    public function isProtected(): bool
    {
        return $this->is_protected;
    }

    /**
     * Check whether this role has been granted a specific privilege.
     */
    public function hasPrivilege(string $slug): bool
    {
        return $this->privileges()->where('slug', $slug)->exists();
    }

    /**
     * Grant one or more privileges to this role.
     *
     * @param  int|array<int>  $privilegeIds
     */
    public function grantPrivileges(int|array $privilegeIds): void
    {
        $this->privileges()->syncWithoutDetaching($privilegeIds);
    }

    /**
     * Revoke one or more privileges from this role.
     *
     * @param  int|array<int>  $privilegeIds
     */
    public function revokePrivileges(int|array $privilegeIds): void
    {
        $this->privileges()->detach($privilegeIds);
    }

    // ─── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        // Prevent deletion of protected roles.
        static::deleting(function (Role $role): void {
            if ($role->isProtected()) {
                throw AdminDashboardException::invalidConfiguration(
                    'roles',
                    "The role [{$role->name}] is protected and cannot be deleted."
                );
            }
        });
    }

    // ─── Factory ─────────────────────────────────────────────────────

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }
}
