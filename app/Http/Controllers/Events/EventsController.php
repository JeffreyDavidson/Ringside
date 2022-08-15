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

class EventsController extends Controller
{
    /**
     * View a list of events.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->authorize('viewList', Event::class);

        return view('events.index');
    }

    /**
     * Show the form for creating a new event.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\View\View
     */
    public function create(Event $event)
    {
        $this->authorize('create', Event::class);

        return view('events.create', [
            'event' => $event,
            'venues' => Venue::pluck('name', 'id'),
        ]);
    }

    /**
     * Create a new event.
     *
     * @param  \App\Http\Requests\Events\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        CreateAction::run(EventData::fromStoreRequest($request));

        return to_route('events.index');
    }

    /**
     * Show the event.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\View\View
     */
    public function show(Event $event)
    {
        $this->authorize('view', $event);

        return view('events.show', [
            'event' => $event->load('venue'),
        ]);
    }

    /**
     * Show the form for editing a given event.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\View\View
     */
    public function edit(Event $event)
    {
        $this->authorize('update', $event);

        return view('events.edit', [
            'event' => $event,
            'venues' => Venue::withTrashed()->pluck('name', 'id'),
        ]);
    }

    /**
     * Update an event.
     *
     * @param  \App\Http\Requests\Events\UpdateRequest  $request
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Event $event)
    {
        UpdateAction::run($event, EventData::fromUpdateRequest($request));

        return to_route('events.index');
    }

    /**
     * Delete an event.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        DeleteAction::run($event);

        return to_route('events.index');
    }
}
