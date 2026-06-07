<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Rabbanist\AdminDashboard\Contracts\AuditLoggerInterface;
use Rabbanist\AdminDashboard\Models\AuditLog;

class AuditLogger implements AuditLoggerInterface
{
    public function __construct(
        protected readonly bool $enabled = true,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function log(string $action, string $description, array $context = []): void
    {
        if (! $this->enabled) {
            return;
        }

        try {
            AuditLog::create([
                'user_id'     => Auth::id(),
                'action'      => $action,
                'description' => $description,
                'context'     => $context,
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
                'performed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let audit logging break the main flow.
            Log::error('[AdminDashboard] Failed to write audit log.', [
                'action'    => $action,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
