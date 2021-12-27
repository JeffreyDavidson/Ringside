<?php

namespace App\Http\Controllers\EventMatches;

use App\Actions\AddMatchForEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\Event;
use App\Models\EventMatch;

class EventMatchesController extends Controller
{
    /**
     * Show the form for creating a new match for a given event.
     *
     * @param  \App\Models\Event $event
     * @return \Illuminate\View\View
     */
    public function create(Event $event)
    {
        $this->authorize('create', EventMatch::class);

        return view('matches.create', [
            'event' => $event,
        ]);
    }

    /**
     * Create a new match for a given event.
     *
     * @param  \App\Models\Event  $event
     * @param  \App\Http\Requests\StoreRequest  $request
     * @param  \App\Actions\AddMatchForEvent $addMatchForEvent
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Event $event, StoreRequest $request, AddMatchForEvent $addMatchForEvent)
    {
        $addMatchForEvent($event, $request->validated());

        return redirect()->route('events.index');
    }
}
