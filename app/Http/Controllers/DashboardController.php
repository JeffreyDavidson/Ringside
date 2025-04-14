<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

final class DashboardController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        return view('dashboard');
    }
}
