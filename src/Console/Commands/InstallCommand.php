<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    protected $signature = 'admin-dashboard:install
                            {--force : Overwrite existing published files}
                            {--rollback : Revert installation steps}';

    protected $description = 'Install Admin Dashboard package with publishing, migrations, and seeding';

    public function handle(): int
    {
        $this->info('Starting Admin Dashboard installation...');
        Log::info('--- Admin Dashboard installation started at '.now());

        if ($this->option('rollback')) {
            return $this->rollback();
        }

        if (! $this->checkLaravelVersion()) {
            $this->error('Laravel version not compatible. Requires Laravel 9 or higher.');
            Log::error('Laravel version incompatibility');
            return self::FAILURE;
        }

        $this->publishAssets();
        if (! $this->runMigrations()) {
            $this->error('Migration failed. Check logs.');
            return self::FAILURE;
        }
        $this->seedData();
        $this->finalMessage();
        Log::info('Installation completed successfully');
        return self::SUCCESS;
    }

    protected function checkLaravelVersion(): bool
    {
        $version = app()->version();
        $major = (int) explode('.', $version)[0];
        return $major >= 9;
    }

    protected function publishAssets(): void
    {
        $this->components->task('Publishing configuration...');
        Artisan::call('vendor:publish', [
            '--tag' => 'admin-dashboard-config',
            '--force' => $this->option('force'),
        ]);
        $this->components->task('Publishing migrations...');
        Artisan::call('vendor:publish', [
            '--tag' => 'admin-dashboard-migrations',
            '--force' => $this->option('force'),
        ]);
        $this->components->task('Publishing assets...');
        Artisan::call('vendor:publish', [
            '--tag' => 'admin-dashboard-assets',
            '--force' => $this->option('force'),
        ]);
        $this->components->task('Publishing views...');
        Artisan::call('vendor:publish', [
            '--tag' => 'admin-dashboard-views',
            '--force' => $this->option('force'),
        ]);
        $this->newLine();
        $this->info('Assets published');
        Log::info('Assets published');
    }

    protected function runMigrations(): bool
    {
        $this->components->task('Running migrations...');
        try {
            $this->backupDatabase();
            Artisan::call('migrate', ['--force' => true]);
            $this->info('Migrations completed');
            Log::info('Migrations completed');
            return true;
        } catch (\Exception $e) {
            Log::error('Migration error: '.$e->getMessage());
            return false;
        }
    }

    protected function backupDatabase(): void
    {
        if ($this->input->isInteractive()) {
            if ($this->confirm('Create a database backup before migrations?', true)) {
                $this->components->task('Creating DB backup...');
                $process = Process::fromShellCommandline(
                    'mysqldump -u'.env('DB_USERNAME').' -p'.env('DB_PASSWORD').' '.env('DB_DATABASE').' > storage/app/db-backup-'.now()->format('Ymd_His').'.sql'
                );
                $process->run();
                if ($process->isSuccessful()) {
                    $this->info('Backup created');
                    Log::info('Database backup created');
                } else {
                    $this->warn('Backup failed, proceeding anyway');
                    Log::warning('Database backup failed');
                }
            }
        }
    }

    protected function seedData(): void
    {
        $this->components->task('Seeding roles...');
        Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);
        $this->components->task('Seeding privileges...');
        Artisan::call('db:seed', ['--class' => 'PrivilegeSeeder', '--force' => true]);
        if ($this->input->isInteractive()) {
            if ($this->confirm('Create a default admin user now?', true)) {
                Artisan::call('admin-dashboard:create-admin');
            }
        }
        Log::info('Seeders executed');
    }

    protected function finalMessage(): void
    {
        $this->info('Installation complete!');
        $this->comment('Next steps:');
        $this->line('- Review config at config/admin-dashboard.php');
        $this->line('- Access the dashboard at /admin');
    }

    protected function rollback(): int
    {
        $this->warn('Rolling back Admin Dashboard installation...');
        if (! $this->confirm('Are you sure you want to rollback? This will drop admin tables and remove published files.', false)) {
            $this->info('Rollback cancelled.');
            return self::SUCCESS;
        }
        // Drop package tables (simple example)
        DB::statement('DROP TABLE IF EXISTS admin_roles, admin_privileges, admin_role_privilege, admin_users');
        $this->components->task('Removing published files');
        $this->removePublishedFiles();
        $this->info('Rollback completed');
        Log::info('Rollback completed');
        return self::SUCCESS;
    }

    protected function removePublishedFiles(): void
    {
        $paths = [
            config_path('admin-dashboard.php'),
            database_path('migrations'),
            public_path('vendor/admin-dashboard'),
            resource_path('views/vendor/admin-dashboard'),
        ];
        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::deleteDirectory($path);
                $this->components->task("Removed {$path}");
            }
        }
    }
}
