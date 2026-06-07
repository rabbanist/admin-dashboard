<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    /**
     * Get package service providers.
     */
    protected function getPackageProviders($app)
    {
        return [
            \Yourvendor\AdminDashboard\AdminDashboardServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function getEnvironmentSetUp($app)
    {
        // Use in-memory SQLite for faster tests.
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Helper to act as admin user.
     */
    protected function actingAsAdmin()
    {
        $adminRole = \Yourvendor\AdminDashboard\Models\Role::firstOrCreate([
            'slug' => 'admin',
        ], [
            'name' => 'Admin',
            'description' => 'Admin role',
            'is_protected' => true,
        ]);
        $admin = \Yourvendor\AdminDashboard\Models\User::factory()->create();
        $admin->roles()->attach($adminRole);
        $this->actingAs($admin);
        return $admin;
    }
}
?>
