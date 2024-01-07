<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Actions\Events\CreateAction;
use App\Actions\Events\DeleteAction;
use App\Actions\Events\UpdateAction;
use App\Data\EventData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreRequest;
use App\Http\Requests\Events\UpdateRequest;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EventsController extends Controller
{
    /**
     * View a list of events.
     */
    public function index(): View
    {
        $this->authorize('viewList', Event::class);

        return view('events.index');
    }

    /**
     * Show the form for creating a new event.
     */
    public function create(Event $event): View
    {
        $this->authorize('create', Event::class);

        return view('events.create', [
            'event' => $event,
            'venues' => Venue::pluck('name', 'id'),
        ]);
    }

    /**
     * Create a new event.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        CreateAction::run(EventData::fromStoreRequest($request));

        return to_route('events.index');
    }

    /**
     * Show the event.
     */
    public function show(Event $event): View
    {
        $this->authorize('view', $event);

        return view('events.show', [
            'event' => $event->load([
                'venue',
                'matches.matchType',
                'matches.referees' => function ($query) {
                    $query->withTrashed();
                },
                'matches.titles' => function ($query) {
                    $query->withTrashed();
                },
                'matches.competitors.competitor' => function ($query) {
                    $query->withTrashed();
                },
            ]),
        ]);
    }

    /**
     * Show the form for editing a given event.
     */
    public function edit(Event $event): View
    {
        $this->authorize('update', $event);

        return view('events.edit', [
            'event' => $event,
            'venues' => Venue::withTrashed()->pluck('name', 'id'),
        ]);
    }

    /**
     * Update an event.
     */
    public function update(UpdateRequest $request, Event $event): RedirectResponse
    {
        UpdateAction::run($event, EventData::fromUpdateRequest($request));

        return to_route('events.index');
    }

    /**
     * Delete an event.
     */
    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        DeleteAction::run($event);

        return to_route('events.index');
    }
}
