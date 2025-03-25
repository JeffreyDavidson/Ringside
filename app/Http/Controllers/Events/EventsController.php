<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class EventsController extends Controller
{
    /**
     * View a list of events.
     */
    public function index(): View
    {
        Gate::authorize('viewList', Event::class);

        return view('events.index');
    }

    /**
     * Show the event.
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
