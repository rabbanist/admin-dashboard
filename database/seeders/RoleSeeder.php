<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Database\Seeders;

use Illuminate\Database\Seeder;
use Rabbanist\AdminDashboard\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the role seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full administrative access.',
                'is_protected' => true,
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Standard user with limited permissions.',
                'is_protected' => false,
            ],
            [
                'name' => 'Editor',
                'slug' => 'editor',
                'description' => 'Can manage content and basic settings.',
                'is_protected' => false,
            ],
            [
                'name' => 'Moderator',
                'slug' => 'moderator',
                'description' => 'Can moderate user generated content.',
                'is_protected' => false,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
