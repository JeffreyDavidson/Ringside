<?php

declare(strict_types=1);

namespace App\Http\Controllers\EventMatches;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Contracts\View\View;

class EventMatchesController extends Controller
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
