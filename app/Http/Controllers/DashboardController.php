<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Tests\Feature\Http\Controllers\DashboardControllerTest;

/**
 * Controller for displaying the dashboard.
 *
 * @see DashboardControllerTest
 */
class DashboardController
{
    /**
     * Display the dashboard.
     *
     * @see DashboardControllerTest::test_invoke_returns_dashboard_view()
     */
    public function __invoke(): View
    {
        return view('dashboard');
    }
}
