<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Contracts\View\View;

class EventMatchesController
{
    /**
     * View a list of events matches.
     */
    public function index(Event $event): View
    {
        return view('event-matches.index', [
            'event' => $event,
        ]);
    }
}
