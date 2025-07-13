<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Shared\Venue;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Http\Controllers\VenuesControllerTest;

/**
 * Controller for managing venues.
 *
 * @see VenuesControllerTest
 */
class VenuesController
{
    /**
     * View a list of venues.
     *
     * @see VenuesControllerTest::test_index_returns_a_view()
     */
    public function index(): View
    {
        Gate::authorize('viewList', Venue::class);

        return view('venues.index');
    }

    /**
     * Show the venue.
     *
     * @see VenuesControllerTest::test_show_returns_a_view()
     */
    public function show(Venue $venue): View
    {
        Gate::authorize('view', $venue);

        return view('venues.show', [
            'venue' => $venue,
        ]);
    }
}
