<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class VenuesController
{
    /**
     * View a list of venues.
     */
    public function index(): View
    {
        Gate::authorize('viewList', Venue::class);

        return view('venues.index');
    }

    /**
     * Show the venue.
     */
    public function show(Venue $venue): View
    {
        Gate::authorize('view', $venue);

        return view('venues.show', [
            'venue' => $venue,
        ]);
    }
}
