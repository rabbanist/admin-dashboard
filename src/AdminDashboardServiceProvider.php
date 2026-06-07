<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Yourvendor\AdminDashboard\Console\Commands\InstallCommand;
use Yourvendor\AdminDashboard\Console\Commands\PublishCommand;
use Yourvendor\AdminDashboard\Contracts\AuditLoggerInterface;
use Yourvendor\AdminDashboard\Contracts\DashboardServiceInterface;
use Yourvendor\AdminDashboard\Http\Middleware\CheckAdminAccess;
use Yourvendor\AdminDashboard\Http\Middleware\CheckPrivilege;
use Yourvendor\AdminDashboard\Http\Middleware\LogAdminActivity;
use Yourvendor\AdminDashboard\Listeners\LogAuthenticationEvent;
use Yourvendor\AdminDashboard\Listeners\LogModelChanges;
use Yourvendor\AdminDashboard\Models\Privilege;
use Yourvendor\AdminDashboard\Models\Role;
use Yourvendor\AdminDashboard\Policies\PrivilegePolicy;
use Yourvendor\AdminDashboard\Policies\RolePolicy;
use Yourvendor\AdminDashboard\Policies\UserPolicy;
use Yourvendor\AdminDashboard\Services\AuditLogger;
use Yourvendor\AdminDashboard\Services\DashboardService;

class AdminDashboardServiceProvider extends ServiceProvider
{
    /**
     * All middleware provided by this package.
     *
     * @var array<string, class-string>
     */
    protected array $middlewareAliases = [
        'admin.access'    => CheckAdminAccess::class,
        'admin.privilege' => CheckPrivilege::class,
        'admin.activity'  => LogAdminActivity::class,
    ];

    /**
     * Register package services into the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/admin-dashboard.php',
            'admin-dashboard'
        );

        $this->registerServices();
    }

    /**
     * Boot package services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerResources();
        $this->registerPublishing();
        $this->registerMiddleware();
        $this->registerBladeDirectives();
        $this->registerPolicies();
        $this->registerEventListeners();
        $this->registerCommands();
    }

    // ─── Service Bindings ────────────────────────────────────────────

    /**
     * Bind services into the container.
     */
    protected function registerServices(): void
    {
        // Main dashboard service — singleton so config is resolved once.
        $this->app->singleton(DashboardServiceInterface::class, function (Application $app): DashboardService {
            return new DashboardService(
                config: $app['config']->get('admin-dashboard'),
            );
        });

        // Convenience alias for facade / resolve('admin-dashboard').
        $this->app->alias(DashboardServiceInterface::class, 'admin-dashboard');

        // Audit logger — singleton with feature-flag guard.
        $this->app->singleton(AuditLoggerInterface::class, function (Application $app): AuditLogger {
            return new AuditLogger(
                enabled: (bool) $app['config']->get('admin-dashboard.features.audit_logs', true),
            );
        });
    }

    // ─── Routes ──────────────────────────────────────────────────────

    /**
     * Register the package routes with the configured prefix and middleware.
     */
    protected function registerRoutes(): void
    {
        $routeFile = __DIR__ . '/../routes/web.php';

        if (! file_exists($routeFile)) {
            return;
        }

        $this->app->make(Router::class)
            ->group([
                'prefix'     => config('admin-dashboard.route_prefix', 'admin'),
                'middleware'  => config('admin-dashboard.middleware', ['web', 'auth']),
                'namespace'  => 'Yourvendor\\AdminDashboard\\Http\\Controllers',
                'as'         => 'admin.',
                'domain'     => config('admin-dashboard.route_domain'),
            ], $routeFile);
    }

    // ─── Resources (Views, Migrations, Translations) ─────────────────

    /**
     * Register views and other package resources.
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'admin-dashboard');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Translations (optional — only if the directory exists).
        $langPath = __DIR__ . '/../resources/lang';
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'admin-dashboard');
        }
    }

    // ─── Publishing ──────────────────────────────────────────────────

    /**
     * Register all publishable assets (config, views, migrations, public assets).
     */
    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Config
        $this->publishes([
            __DIR__ . '/../config/admin-dashboard.php' => config_path('admin-dashboard.php'),
        ], 'admin-dashboard-config');

        // Views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/admin-dashboard'),
        ], 'admin-dashboard-views');

        // Migrations
        $this->publishesMigrations([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'admin-dashboard-migrations');

        // Public assets (CSS, JS, images)
        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/admin-dashboard'),
        ], 'admin-dashboard-assets');

        // Convenience group — publish everything at once.
        $this->publishes([
            __DIR__ . '/../config/admin-dashboard.php' => config_path('admin-dashboard.php'),
            __DIR__ . '/../resources/views'            => resource_path('views/vendor/admin-dashboard'),
            __DIR__ . '/../public'                     => public_path('vendor/admin-dashboard'),
        ], 'admin-dashboard');
    }

    // ─── Middleware ──────────────────────────────────────────────────

    /**
     * Register middleware aliases with the router.
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        foreach ($this->middlewareAliases as $alias => $class) {
            $router->aliasMiddleware($alias, $class);
        }
    }

    // ─── Blade Directives ────────────────────────────────────────────

    /**
     * Register custom Blade directives for the admin dashboard.
     */
    protected function registerBladeDirectives(): void
    {
        // @admin ... @endadmin — renders content only for admin users.
        Blade::if('admin', function (): bool {
            $user = auth()->user();

            if (is_null($user)) {
                return false;
            }

            $gateName = config('admin-dashboard.authorization.gate', 'access-admin-dashboard');

            return Gate::forUser($user)->allows($gateName);
        });

        // @adminFeature('feature_name') ... @endadminFeature
        Blade::if('adminFeature', function (string $feature): bool {
            return (bool) config("admin-dashboard.features.{$feature}", false);
        });

        // @adminTitle — outputs the configured dashboard title.
        Blade::directive('adminTitle', function (): string {
            return "<?php echo e(config('admin-dashboard.title', 'Admin Dashboard')); ?>";
        });

        // @adminRoute('route.name') — generates a full URL for a named admin route.
        Blade::directive('adminRoute', function (string $expression): string {
            return "<?php echo e(route({$expression})); ?>";
        });
    }

    // ─── Policies ────────────────────────────────────────────────────

    /**
     * Register authorization policies.
     */
    protected function registerPolicies(): void
    {
        $userModel = config('admin-dashboard.user_model', \App\Models\User::class);

        if (class_exists($userModel)) {
            Gate::policy($userModel, UserPolicy::class);
        }

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Privilege::class, PrivilegePolicy::class);

        // Define the dashboard access gate used by middleware & Blade directives.
        Gate::define(
            config('admin-dashboard.authorization.gate', 'access-admin-dashboard'),
            function ($user): bool {
                // Super-admins always pass.
                $superAdmins = config('admin-dashboard.authorization.super_admins', []);

                if (in_array($user->email, $superAdmins, true)) {
                    return true;
                }

                // Delegate to the user model if it implements an `isAdmin()` method.
                if (method_exists($user, 'isAdmin')) {
                    return (bool) $user->isAdmin();
                }

                // Fallback: deny access unless explicitly granted.
                return false;
            }
        );
    }

    // ─── Event Listeners ─────────────────────────────────────────────

    /**
     * Register event listeners for audit logging and activity tracking.
     */
    protected function registerEventListeners(): void
    {
        $features = config('admin-dashboard.features', []);

        // Audit authentication events.
        if (! empty($features['audit_logs'])) {
            Event::listen(\Illuminate\Auth\Events\Login::class, LogAuthenticationEvent::class);
            Event::listen(\Illuminate\Auth\Events\Failed::class, LogAuthenticationEvent::class);
            Event::listen(\Illuminate\Auth\Events\Logout::class, LogAuthenticationEvent::class);
        }

        // Track model changes for activity log.
        if (! empty($features['activity_log'])) {
            Event::listen('eloquent.created: *', [LogModelChanges::class, 'handleCreated']);
            Event::listen('eloquent.updated: *', [LogModelChanges::class, 'handleUpdated']);
            Event::listen('eloquent.deleted: *', [LogModelChanges::class, 'handleDeleted']);
        }
    }

    // ─── Console Commands ────────────────────────────────────────────

    /**
     * Register Artisan commands provided by this package.
     */
    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
            PublishCommand::class,
            \Yourvendor\AdminDashboard\Console\Commands\CreateAdminCommand::class,
        ]);
    }
}
