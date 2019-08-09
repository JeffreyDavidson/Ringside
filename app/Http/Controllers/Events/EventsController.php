<?php

namespace App\Http\Controllers\Events;

use App\Models\Event;
use Illuminate\Http\Request;
use App\Filters\EventFilters;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;

class EventsController extends Controller
{
    /**
     * View a list of events.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Yajra\DataTables\DataTables  $table
     * @return \Illuminate\View\View
     */
    public function index(Request $request, DataTables $table, EventFilters $requestFilter)
    {
        $this->authorize('viewList', Event::class);

        if ($request->ajax()) {
            $query = Event::query();
            $requestFilter->apply($query);

            return $table->eloquent($query)
                ->addColumn('action', 'events.partials.action-cell')
                ->filterColumn('id', function ($query, $keyword) {
                    $query->where($query->qualifyColumn('id'), $keyword);
                })
                ->toJson();
        }

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
