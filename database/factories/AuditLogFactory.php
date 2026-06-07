<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Yourvendor\AdminDashboard\Models\AuditLog;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id'      => null,
            'action'       => fake()->randomElement([
                'login', 'logout', 'login_failed',
                'model_created', 'model_updated', 'model_deleted',
                'page_view', 'settings_changed', 'export',
            ]),
            'description'  => fake()->sentence(),
            'context'      => [
                'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
                'url'    => fake()->url(),
            ],
            'ip_address'   => fake()->ipv4(),
            'user_agent'   => fake()->userAgent(),
            'performed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Assign the audit log to a specific user.
     */
    public function forUser(int $userId): static
    {
        return $this->state(fn () => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Create a login event entry.
     */
    public function login(): static
    {
        return $this->state(fn () => [
            'action'      => 'login',
            'description' => 'User logged in.',
        ]);
    }

    /**
     * Create a model change entry.
     */
    public function modelChange(string $action = 'model_updated'): static
    {
        return $this->state(fn () => [
            'action'      => $action,
            'description' => "Model {$action}.",
            'context'     => [
                'model_class' => 'App\\Models\\User',
                'model_id'    => fake()->randomNumber(3),
            ],
        ]);
    }
}
