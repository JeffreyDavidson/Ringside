<?php

declare(strict_types=1);

namespace App\Http\Controllers\Venues;

use App\Models\Events\Venue;
use Illuminate\Contracts\View\View;

class VenueController
{
    public function index(): View
    {
        return view('venues.index');
    }

    public function show(Venue $venue): View
    {
        return view('venues.show', ['venue' => $venue]);
    }
}
