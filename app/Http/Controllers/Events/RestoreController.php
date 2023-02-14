<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Actions\Events\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

class RestoreController extends Controller
{
    /**
     * Restore a deleted scheduled event.
     */
    public function __invoke(int $eventId): RedirectResponse
    {
        $event = Event::onlyTrashed()->findOrFail($eventId);

        $this->authorize('restore', $event);

        try {
            RestoreAction::run($event);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('events.show', $event);
    }
}
