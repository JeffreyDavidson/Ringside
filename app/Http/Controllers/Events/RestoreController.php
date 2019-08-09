<?php

namespace App\Http\Controllers\Events;

use App\Models\Event;
use App\Http\Controllers\Controller;

class RestoreController extends Controller
{
    /**
     * Restore a deleted scheduled event.
     *
     * @param  int  $eventId
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke($eventId)
    {
        $event = Event::onlyTrashed()->findOrFail($eventId);

        $this->authorize('restore', $event);

        $event->restore();

        return redirect()->route('events.index');
    }
}
