<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URI prefix for all admin dashboard routes. All routes registered by
    | this package will be prefixed with this value (e.g., /admin/dashboard).
    |
    */

    'route_prefix' => env('ADMIN_DASHBOARD_PREFIX', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Route Domain
    |--------------------------------------------------------------------------
    |
    | Optionally restrict admin routes to a specific domain/subdomain.
    | Leave null to use the application's default domain.
    |
    */

    'route_domain' => env('ADMIN_DASHBOARD_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to all admin dashboard routes. You may add your own
    | middleware classes here to enforce authentication, role checks, etc.
    |
    */

    'middleware' => [
        'web',
        'auth',
        \Yourvendor\AdminDashboard\Http\Middleware\AdminAccessMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Title
    |--------------------------------------------------------------------------
    |
    | The title displayed in the admin dashboard header, browser tab, and
    | meta tags. This can be overridden per-view if needed.
    |
    */

    'title' => env('ADMIN_DASHBOARD_TITLE', 'Admin Dashboard'),

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of the User model used throughout the
    | admin dashboard. Change this if you use a custom User model.
    |
    */

    'user_model' => App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for listing pages within the dashboard.
    |
    */

    'pagination' => [
        'per_page'    => 25,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Uploads
    |--------------------------------------------------------------------------
    |
    | Configure the filesystem disk and path conventions for file uploads
    | handled through the admin dashboard.
    |
    */

    'uploads' => [
        'disk'       => env('ADMIN_DASHBOARD_UPLOAD_DISK', 'public'),
        'path'       => 'admin-uploads',
        'max_size'   => 10240, // kilobytes
        'allowed_mimes' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv',
            'zip', 'mp4',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Toggle optional features on or off. Disabling a feature will prevent
    | its routes, views, and background processing from being registered.
    |
    */

    'features' => [
        'two_factor_auth' => env('ADMIN_2FA_ENABLED', false),
        'audit_logs'      => env('ADMIN_AUDIT_LOGS_ENABLED', true),
        'activity_log'    => env('ADMIN_ACTIVITY_LOG_ENABLED', true),
        'notifications'   => env('ADMIN_NOTIFICATIONS_ENABLED', true),
        'file_manager'    => env('ADMIN_FILE_MANAGER_ENABLED', false),
        'api_tokens'      => env('ADMIN_API_TOKENS_ENABLED', false),
        'user_impersonation' => env('ADMIN_IMPERSONATION_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | Configure how the dashboard determines admin access. The gate callback
    | is used by the AdminAccessMiddleware and Blade directives.
    |
    */

    'authorization' => [
        'gate'      => 'access-admin-dashboard',
        'super_admins' => [], // email addresses that always have full access
    ],

    /*
    |--------------------------------------------------------------------------
    | Date & Time
    |--------------------------------------------------------------------------
    |
    | Default date/time display format used throughout the admin dashboard.
    |
    */

    'date_format' => 'M d, Y',
    'datetime_format' => 'M d, Y H:i',

];
