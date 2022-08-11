<?php

declare(strict_types=1);

namespace App\Http\Controllers\Venues;

use App\Actions\Venues\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Venue;

class RestoreController extends Controller
{
    /**
     * Restore a deleted venue.
     *
     * @param  int  $venueId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke($venueId)
    {
        $venue = Venue::onlyTrashed()->findOrFail($venueId);

        $this->authorize('restore', $venue);

        RestoreAction::run($venue);

        return to_route('venues.index');
    }
}
