<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ViewModels\DashboardViewModel;
use Illuminate\Contracts\View\View;

/**
 * Controller for displaying the dashboard.
 */
class DashboardController
{
    /**
     * Display the promotion overview.
     */
    public function __invoke(DashboardViewModel $dashboard): View
    {
        return view('dashboard', ['dashboard' => $dashboard]);
    }
}
