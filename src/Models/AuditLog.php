<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Rabbanist\AdminDashboard\Database\Factories\AuditLogFactory;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'admin_audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'context',
        'ip_address',
        'user_agent',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'context'      => 'array',
            'performed_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────

    /**
     * The user who performed the audited action.
     */
    public function user(): BelongsTo
    {
        $userModel = config('admin-dashboard.user_model', \App\Models\User::class);

        return $this->belongsTo($userModel);
    }

    // ─── Scopes ──────────────────────────────────────────────────────

    /**
     * Scope to a specific action type.
     */
    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to entries within a date range.
     */
    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('performed_at', [$from, $to]);
    }

    /**
     * Scope to the most recent entries.
     */
    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->orderByDesc('performed_at')->limit($limit);
    }

    // ─── Factory ─────────────────────────────────────────────────────

    protected static function newFactory(): AuditLogFactory
    {
        return AuditLogFactory::new();
    }
}
