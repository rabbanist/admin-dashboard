<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Rabbanist\AdminDashboard\Models\Role;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = fake()->unique()->jobTitle() . ' ' . fake()->unique()->word();

        return [
            'name'         => $name,
            'slug'         => Str::slug($name),
            'description'  => fake()->sentence(),
            'is_protected' => false,
        ];
    }

    /**
     * Create a protected (built-in) role.
     */
    public function protected(): static
    {
        return $this->state(fn () => [
            'is_protected' => true,
        ]);
    }

    /**
     * Create the standard "admin" role.
     */
    public function admin(): static
    {
        return $this->state(fn () => [
            'name'         => 'Admin',
            'slug'         => 'admin',
            'description'  => 'Full administrative access.',
            'is_protected' => true,
        ]);
    }

    /**
     * Create the standard "super-admin" role.
     */
    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'name'         => 'Super Admin',
            'slug'         => 'super-admin',
            'description'  => 'Unrestricted access to all system features.',
            'is_protected' => true,
        ]);
    }

    /**
     * Create a basic "editor" role.
     */
    public function editor(): static
    {
        return $this->state(fn () => [
            'name'         => 'Editor',
            'slug'         => 'editor',
            'description'  => 'Can create and edit content.',
            'is_protected' => false,
        ]);
    }

    /**
     * Create a basic "viewer" role.
     */
    public function viewer(): static
    {
        return $this->state(fn () => [
            'name'         => 'Viewer',
            'slug'         => 'viewer',
            'description'  => 'Read-only access to the dashboard.',
            'is_protected' => false,
        ]);
    }
}
