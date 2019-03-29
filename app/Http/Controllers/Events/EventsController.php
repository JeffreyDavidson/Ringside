<?php

namespace App\Http\Controllers\Events;

use App\Models\Event;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;

class EventsController extends Controller
{
    /**
     * Retrieve events of a specific state.
     *
     * @param  string  $state
     * @return \Illuminate\Http\Response
     */
    public function index($state = 'scheduled')
    {
        $this->authorize('viewList', Event::class);

        $events = Event::hasState($state)->get();

        return view('events.index', compact('events'));
    }

    /**
     * Show the form for creating an event.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Event::class);

        return view('events.create');
    }

    /**
     * Create a new event.
     *
     * @param  \App\Http\Requests\StoreEventRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreEventRequest $request)
    {
        Event::create($request->all());

        return redirect()->route('events.index');
    }

    /**
     * Show the event.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function show(Event $event)
    {
        $event->load('venue');

        return response()->view('events.show', compact('event'));
    }

    /**
     * Show the form for creating an event.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Event $event)
    {
        $this->authorize('update', Event::class);

        return view('events.edit', compact('event'));
    }

    /**
     * Create a new event.
     *
     * @param  \App\Http\Requests\UpdateEventRequest  $request
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        $event->update($request->all());

        return redirect()->route('events.index');
    }

    /**
     * Delete an event.
     *
     * @param  App\Models\Event  $event
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('events.index');
    }

    /**
     * Restore a deleted scheduled event.
     *
     * @param  int  $eventId
     * @return \lluminate\Http\RedirectResponse
     */
    public function restore($eventId)
    {
        $event = Event::onlyTrashed()->findOrFail($eventId);

        $this->authorize('restore', $event);

        $event->restore();

        return redirect()->route('events.index');
    }
}
