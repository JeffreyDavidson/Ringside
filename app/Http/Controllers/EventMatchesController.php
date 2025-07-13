<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Http\Controllers\EventMatchesControllerTest;

/**
 * Controller for managing event matches.
 *
 * @see EventMatchesControllerTest
 */
class EventMatchesController
{
    /**
     * View a list of events matches.
     *
     * @see EventMatchesControllerTest::test_index_returns_a_view()
     */
    public function index(Event $event): View
    {
        Gate::authorize('viewList', EventMatch::class);

        return view('event-matches.index', [
            'event' => $event,
        ]);
    }
}
