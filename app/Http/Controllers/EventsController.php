<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Events\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Http\Controllers\EventsControllerTest;

/**
 * Controller for managing events.
 *
 * @see EventsControllerTest
 */
class EventsController
{
    /**
     * View a list of events.
     *
     * @see EventsControllerTest::test_index_returns_a_view()
     */
    public function index(): View
    {
        Gate::authorize('viewList', Event::class);

        return view('events.index');
    }

    /**
     * Show the event.
     *
     * @see EventsControllerTest::test_show_returns_a_view()
     */
    public function show(Event $event): View
    {
        Gate::authorize('view', $event);

        return view('events.show', [
            'event' => $event->load([
                'venue',
                'matches.matchType',
                'matches.referees',
                'matches.titles',
                'matches.competitors.competitor',
            ]),
        ]);
    }
}
