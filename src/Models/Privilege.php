<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Yourvendor\AdminDashboard\Database\Factories\PrivilegeFactory;

class Privilege extends Model
{
    use HasFactory;

    protected $table = 'admin_privileges';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'resource_type',
        'module',
    ];

    // ─── Relationships ───────────────────────────────────────────────

    /**
     * The roles that include this privilege.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'admin_role_privilege',
            'privilege_id',
            'role_id',
        );
    }

    /**
     * Users who have been directly assigned this privilege.
     */
    public function users(): BelongsToMany
    {
        $userModel = config('admin-dashboard.user_model', \App\Models\User::class);

        return $this->belongsToMany(
            $userModel,
            'admin_privilege_user',
            'privilege_id',
            'user_id',
        )->withPivot('assigned_at');
    }

    // ─── Scopes ──────────────────────────────────────────────────────

    /**
     * Scope privileges to a specific resource type.
     *
     * Usage: Privilege::byResource('users')->get()
     */
    public function scopeByResource(Builder $query, string $resourceType): Builder
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope privileges to a specific module.
     *
     * Usage: Privilege::byModule('content')->get()
     */
    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Scope privileges matching a slug pattern.
     *
     * Usage: Privilege::slugLike('users.%')->get()
     */
    public function scopeSlugLike(Builder $query, string $pattern): Builder
    {
        return $query->where('slug', 'like', $pattern);
    }

    // ─── Factory ─────────────────────────────────────────────────────

    protected static function newFactory(): PrivilegeFactory
    {
        return PrivilegeFactory::new();
    }
}
