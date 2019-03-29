<?php

namespace App\Http\Controllers\Events;

use App\Models\Event;
use App\Http\Controllers\Controller;

class ArchivedEventsController extends Controller
{
    /**
     * Archive an event.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Event $event)
    {
        $this->authorize('archive', $event);

        $event->archive();

        return redirect()->route('events.index', ['state' => 'archived']);
    }
}
