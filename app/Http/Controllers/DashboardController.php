<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        return view('dashboard');
    }
}
