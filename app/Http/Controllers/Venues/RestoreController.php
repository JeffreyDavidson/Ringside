<?php

declare(strict_types=1);

namespace App\Http\Controllers\Venues;

use App\Actions\Venues\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RestoreController extends Controller
{
    /**
     * Restore a deleted venue.
     */
    public function __invoke(int $venueId): RedirectResponse
    {
        $venue = Venue::onlyTrashed()->findOrFail($venueId);

        Gate::authorize('restore', $venue);

        RestoreAction::run($venue);

        return to_route('venues.index');
    }
}
