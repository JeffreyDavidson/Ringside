<?php

namespace App\Http\Controllers\Venues;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Services\VenueService;

class RestoreController extends Controller
{
    /**
     * Restore a deleted venue.
     *
     * @param  int  $venueId
     * @param  \App\Services\VenueService  $venueService
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke($venueId, VenueService $venueService)
    {
        $venue = Venue::onlyTrashed()->findOrFail($venueId);

        $this->authorize('restore', $venue);

        $venueService->restore($venue);

        return redirect()->route('venues.index');
    }
}
