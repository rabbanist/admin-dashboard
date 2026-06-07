#!/usr/bin/env bash

# ------------------------------------------------------------
# Deploy admin-dashboard package in a fresh Laravel project
# ------------------------------------------------------------
# This script assumes you are in the root of a Laravel application
# that has already required the package via Composer.
# It automates the steps needed to make the package live in production.

set -e

# 1. Install Composer dependencies (no dev packages)
composer install --optimize-autoloader --no-dev

# 2. Run the admin-dashboard installer (non‑interactive, force overwrite)
php artisan admin-dashboard:install --no-interaction --force

# 3. Cache configuration, routes and views for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Restart queue workers (adjust if you use a process manager)
if command -v supervisorctl >/dev/null 2>&1; then
    echo "Restarting Laravel queue workers via Supervisor…"
    supervisorctl restart laravel-queue:* || true
fi

# 5. Run any pending migrations (should already be run by installer)
php artisan migrate --force

# 6. Clear any old caches just in case
php artisan cache:clear

# 7. Give a friendly message
echo "✅ Admin Dashboard is now live! Visit /admin in your browser."
