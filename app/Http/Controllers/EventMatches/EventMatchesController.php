<?php

namespace App\Http\Controllers\EventMatches;

use App\Actions\Events\AddMatchForEvent;
use App\DataTransferObjects\EventMatchData;
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
    public function create(Event $event, EventMatch $match)
    {
        $this->authorize('create', EventMatch::class);

        return view('matches.create', [
            'event' => $event,
            'match' => $match,
        ]);
    }

    /**
     * Create a new match for a given event.
     *
     * @param  \App\Models\Event  $event
     * @param  \App\Http\Requests\EventMatches\StoreRequest  $request
     * @param  \App\Actions\Events\AddMatchForEvent $addMatchForEvent
     * @param  \App\DataTransferObjects\EventMatchData $eventMatchData
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(
        Event $event,
        StoreRequest $request,
        AddMatchForEvent $addMatchForEvent,
        EventMatchData $eventMatchData
    ) {
        $addMatchForEvent($event, $eventMatchData->fromStoreRequest($request));

        return redirect()->route('events.index');
    }
}
