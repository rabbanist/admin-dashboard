<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Facades;

use Illuminate\Support\Facades\Facade;
use Yourvendor\AdminDashboard\Contracts\DashboardServiceInterface;

/**
 * @method static string title()
 * @method static bool   featureEnabled(string $feature)
 * @method static int    perPage()
 * @method static int    maxPerPage()
 * @method static string uploadDisk()
 * @method static string uploadPath()
 * @method static array  config()
 *
 * @see \Yourvendor\AdminDashboard\Services\DashboardService
 */
class AdminDashboard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DashboardServiceInterface::class;
    }
}
