<?php

namespace App\Http\Controllers\Events;

use App\DataTransferObjects\EventData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreRequest;
use App\Http\Requests\Events\UpdateRequest;
use App\Models\Event;
use App\Services\EventService;

class EventsController extends Controller
{
    public EventService $eventService;

    /**
     * Create a new events controller instance.
     *
     * @param \App\Services\EventService $eventService
     */
    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

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
     * @param  \App\Models\Event $event
     *
     * @return \Illuminate\View\View
     */
    public function create(Event $event)
    {
        $this->authorize('create', Event::class);

        return view('events.create', [
            'event' => $event,
        ]);
    }

    /**
     * Create a new event.
     *
     * @param  \App\Http\Requests\Events\StoreRequest  $request
     * @param  \App\DataTransferObjects\EventData $eventData
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request, EventData $eventData)
    {
        $this->eventService->create($eventData->fromStoreRequest($request));

        return redirect()->route('events.index');
    }

    /**
     * Show the event.
     *
     * @param  \App\Models\Event  $event
     *
     * @return \Illuminate\View\View
     */
    public function show(Event $event)
    {
        $this->authorize('view', $event);

        if ($event->venue_id !== null) {
            $event->load('venue');
        }

        return view('events.show', [
            'event' => $event,
        ]);
    }

    /**
     * Show the form for editing a given event.
     *
     * @param  \App\Models\Event $event
     *
     * @return \Illuminate\View\View
     */
    public function edit(Event $event)
    {
        $this->authorize('update', $event);

        return view('events.edit', [
            'event' => $event,
        ]);
    }

    /**
     * Update an event.
     *
     * @param  \App\Http\Requests\Events\UpdateRequest  $request
     * @param  \App\Models\Event  $event
     * @param  \App\DataTransferObjects\EventData $eventData
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Event $event, EventData $eventData)
    {
        $this->eventService->update($event, $eventData->fromUpdateRequest($request));

        return redirect()->route('events.index');
    }

    /**
     * Delete an event.
     *
     * @param  \App\Models\Event  $event
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $this->eventService->delete($event);

        return redirect()->route('events.index');
    }
}
