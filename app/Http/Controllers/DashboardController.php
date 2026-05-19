<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Controller for displaying the dashboard.
 */
class DashboardController
{
    /**
     * Display the dashboard.
     */
    public function __invoke(): View
    {
        return view('dashboard');
    }
}
