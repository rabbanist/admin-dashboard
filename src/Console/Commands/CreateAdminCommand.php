<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Yourvendor\AdminDashboard\Models\Role;
use App\Models\User; // Assuming default user model

class CreateAdminCommand extends Command
{
    protected $signature = 'admin-dashboard:create-admin
                            {--name= : Admin name}
                            {--email= : Admin email}
                            {--password= : Admin password}
                            {--no-interaction : Run without prompts}';

    protected $description = 'Interactively create a default admin user for Admin Dashboard';

    public function handle(): int
    {
        $this->info('Creating default admin user');

        $name = $this->option('name') ?? $this->ask('Admin name');
        $email = $this->option('email') ?? $this->ask('Admin email');
        $password = $this->option('password') ?? $this->secret('Admin password (min 8 chars)');

        // Validate inputs
        $validator = validator([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->components->error('Validation failed:');
            foreach ($validator->errors()->all() as $msg) {
                $this->line(" - $msg");
            }
            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            // Assign admin role (assumes role slug 'admin')
            $adminRole = Role::where('slug', 'admin')->first();
            if ($adminRole) {
                $user->roles()->attach($adminRole->id);
            }
        });

        $this->info('Admin user created successfully!');
        $this->line('Credentials:');
        $this->line(" Email: $email");
        $this->line(" Password: $password");
        $this->line('You may now log in via the admin dashboard.');
        return self::SUCCESS;
    }
}
?>
