<?php

namespace App\Http\Controllers\Venues;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Repositories\VenueRepository;

class RestoreController extends Controller
{
    /**
     * Restore a deleted venue.
     *
     * @param  int  $venueId
     * @param  \App\Repositories\VenueRepository  $venueRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke($venueId, VenueRepository $venueRepository)
    {
        $venue = Venue::onlyTrashed()->findOrFail($venueId);

        $this->authorize('restore', $venue);

        $venueRepository->restore($venue);

        return redirect()->route('venues.index');
    }
}
