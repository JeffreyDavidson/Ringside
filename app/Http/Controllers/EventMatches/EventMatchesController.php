<?php

declare(strict_types=1);

namespace App\Http\Controllers\EventMatches;

use App\Actions\EventMatches\AddMatchForEventAction;
use App\Data\EventMatchData;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\Event;
use App\Models\EventMatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventMatchesController extends Controller
{
    /**
     * Show the form for creating a new match for a given event.
     */
    public function create(Event $event, EventMatch $match): View
    {
        $this->authorize('create', EventMatch::class);

        return view('matches.create', [
            'event' => $event,
            'match' => $match,
        ]);
    }

    /**
     * Create a new match for a given event.
     */
    public function store(Event $event, StoreRequest $request): RedirectResponse
    {
        AddMatchForEventAction::run($event, EventMatchData::fromStoreRequest($request));

        return to_route('events.matches.index', $event);
    }
}
