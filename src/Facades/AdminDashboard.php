<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Facades;

use Illuminate\Support\Facades\Facade;
use Rabbanist\AdminDashboard\Contracts\DashboardServiceInterface;

/**
 * @method static string title()
 * @method static bool   featureEnabled(string $feature)
 * @method static int    perPage()
 * @method static int    maxPerPage()
 * @method static string uploadDisk()
 * @method static string uploadPath()
 * @method static array  config()
 *
 * @see \Rabbanist\AdminDashboard\Services\DashboardService
 */
class AdminDashboard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DashboardServiceInterface::class;
    }
}
