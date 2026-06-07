<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Rabbanist\AdminDashboard\Contracts\AuditLoggerInterface;

class LogModelChanges
{
    public function __construct(
        protected readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Handle the "created" Eloquent event.
     */
    public function handleCreated(string $event, array $data): void
    {
        $this->logChange('created', $data[0] ?? null);
    }

    /**
     * Handle the "updated" Eloquent event.
     */
    public function handleUpdated(string $event, array $data): void
    {
        $this->logChange('updated', $data[0] ?? null);
    }

    /**
     * Handle the "deleted" Eloquent event.
     */
    public function handleDeleted(string $event, array $data): void
    {
        $this->logChange('deleted', $data[0] ?? null);
    }

    /**
     * Write the model change to the audit log.
     */
    protected function logChange(string $action, mixed $model): void
    {
        if (! $model instanceof Model) {
            return;
        }

        // Don't log changes to the audit log table itself (prevents recursion).
        if ($model instanceof \Rabbanist\AdminDashboard\Models\AuditLog) {
            return;
        }

        try {
            $context = [
                'model_class' => get_class($model),
                'model_id'    => $model->getKey(),
            ];

            if ($action === 'updated') {
                $context['changes'] = $model->getChanges();
            }

            $this->auditLogger->log(
                action: "model_{$action}",
                description: sprintf(
                    '%s %s #%s by user #%s',
                    class_basename($model),
                    $action,
                    $model->getKey(),
                    Auth::id() ?? 'system',
                ),
                context: $context,
            );
        } catch (\Throwable $e) {
            Log::warning('[AdminDashboard] Failed to log model change.', [
                'action'    => $action,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
