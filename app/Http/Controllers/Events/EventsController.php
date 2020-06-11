<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreRequest;
use App\Http\Requests\Events\UpdateRequest;
use App\Models\Event;

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
     * @return \Illuminate\View\View
     */
    public function create(Event $event)
    {
        $this->authorize('create', Event::class);

        return view('events.create', compact('event'));
    }

    /**
     * Create a new event.
     *
     * @param  App\Http\Requests\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        Event::create($request->all());

        return redirect()->route('events.index');
    }

    /**
     * Show the event.
     *
     * @param  App\Models\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function show(Event $event)
    {
        $this->authorize('view', $event);

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
        $this->authorize('update', $event);

        return view('events.edit', compact('event'));
    }

    /**
     * Create a new event.
     *
     * @param  App\Http\Requests\Events\UpdateRequest  $request
     * @param  App\Models\Event  $event
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Event $event)
    {
        $event->update($request->all());

        return redirect()->route('events.index');
    }

    /**
     * Delete an event.
     *
     * @param  App\Models\Event  $event
     * @return \Illuminate\Http\RedirectResponse
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore($eventId)
    {
        $event = Event::onlyTrashed()->findOrFail($eventId);

        $this->authorize('restore', $event);

        $event->restore();

        return redirect()->route('events.index');
    }
}
