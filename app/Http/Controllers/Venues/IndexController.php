<?php

declare(strict_types=1);

namespace App\Http\Controllers\Venues;

use App\Models\Events\Venue;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class IndexController
{
    public function __invoke(): View
    {
        Gate::authorize('viewList', Venue::class);

        return view('venues.index');
    }
}