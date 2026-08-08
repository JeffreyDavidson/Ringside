<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Models\Events\Event;
use Illuminate\Contracts\View\View;

class ShowController
{
    public function __invoke(Event $event): View
    {
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
