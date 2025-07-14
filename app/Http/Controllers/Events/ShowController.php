<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Models\Events\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ShowController
{
    public function __invoke(Event $event): View
    {
        Gate::authorize('view', $event);

        return view('events.show', [
            'event' => $event->load([
                'venue',
                'matches.matchType',
                'matches.referees',
                'matches.titles',
                'matches.competitors.competitor',
            ]),
        ]);
    }
}