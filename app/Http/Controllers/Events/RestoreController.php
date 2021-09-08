<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\EventService;

class RestoreController extends Controller
{
    /**
     * Restore a deleted scheduled event.
     *
     * @param  int  $eventId
     * @param  \App\Services\EventService $eventService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(int $eventId, EventService $eventService)
    {
        $event = Event::onlyTrashed()->findOrFail($eventId);

        $this->authorize('restore', $event);

        $eventService->restore($event);

        return redirect()->route('events.index');
    }
}
