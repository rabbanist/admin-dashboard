<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Yourvendor\AdminDashboard\Models\Privilege;

/**
 * @extends Factory<Privilege>
 */
class PrivilegeFactory extends Factory
{
    protected $model = Privilege::class;

    /**
     * Standard CRUD actions used to generate realistic privilege names.
     */
    protected static array $actions = ['view', 'create', 'update', 'delete', 'export'];

    /**
     * Example resource types for generating realistic data.
     */
    protected static array $resources = ['users', 'posts', 'pages', 'media', 'settings', 'roles'];

    public function definition(): array
    {
        $action   = fake()->randomElement(static::$actions);
        $resource = fake()->unique()->randomElement(static::$resources);
        $name     = ucfirst($action) . ' ' . ucfirst($resource);

        return [
            'name'          => $name,
            'slug'          => Str::slug($name, '.'),
            'description'   => fake()->sentence(),
            'resource_type' => $resource,
            'module'        => fake()->randomElement(['core', 'content', 'system', 'reports']),
        ];
    }

    /**
     * Tie the privilege to a specific resource type.
     */
    public function forResource(string $resource): static
    {
        return $this->state(fn () => [
            'resource_type' => $resource,
        ]);
    }

    /**
     * Tie the privilege to a specific module.
     */
    public function forModule(string $module): static
    {
        return $this->state(fn () => [
            'module' => $module,
        ]);
    }

    /**
     * Generate a full CRUD privilege set for a resource.
     *
     * Usage:
     *   Privilege::factory()->crud('users')->create();
     *   // creates: view.users, create.users, update.users, delete.users
     */
    public function crud(string $resource): static
    {
        return $this->state(fn () => [
            'resource_type' => $resource,
        ])->sequence(
            ...array_map(fn (string $action) => [
                'name' => ucfirst($action) . ' ' . ucfirst($resource),
                'slug' => "{$action}.{$resource}",
            ], ['view', 'create', 'update', 'delete'])
        );
    }
}
