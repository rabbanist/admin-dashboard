<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Yourvendor\AdminDashboard\Contracts\DashboardServiceInterface;

class DashboardController extends Controller
{
    public function __construct(
        protected readonly DashboardServiceInterface $dashboard,
    ) {}

    /**
     * Display the main admin dashboard.
     */
    public function index(Request $request)
    {
        return view('admin-dashboard::dashboard.index', [
            'title'    => $this->dashboard->title(),
            'features' => config('admin-dashboard.features', []),
        ]);
    }
}
