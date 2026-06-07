<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;
use Rabbanist\AdminDashboard\Contracts\AuditLoggerInterface;

class LogAuthenticationEvent
{
    public function __construct(
        protected readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Handle Login, Logout, and Failed authentication events.
     */
    public function handle(Login|Logout|Failed $event): void
    {
        try {
            $action = match (true) {
                $event instanceof Login  => 'login',
                $event instanceof Logout => 'logout',
                $event instanceof Failed => 'login_failed',
            };

            $user = $event->user ?? null;

            $this->auditLogger->log(
                action: $action,
                description: sprintf(
                    'User %s %s.',
                    $user?->email ?? 'unknown',
                    str_replace('_', ' ', $action),
                ),
                context: [
                    'user_id' => $user?->getKey(),
                    'guard'   => $event->guard ?? 'web',
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('[AdminDashboard] Failed to log auth event.', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
