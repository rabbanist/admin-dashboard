<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Contracts;

interface AuditLoggerInterface
{
    /**
     * Log an action performed in the admin dashboard.
     *
     * @param  string       $action      A short verb describing the action (e.g., "created", "deleted").
     * @param  string       $description Human-readable description of what happened.
     * @param  array<string, mixed>  $context     Additional key-value context to store.
     */
    public function log(string $action, string $description, array $context = []): void;

    /**
     * Determine whether audit logging is currently enabled.
     */
    public function isEnabled(): bool;
}
