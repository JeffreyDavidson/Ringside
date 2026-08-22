<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Models\Events\Event;
use Illuminate\Contracts\View\View;

class EventsController
{
    public function index(): View
    {
        return view('events.index');
    }

    public function show(Event $event): View
    {
        return view('events.show', [
            'event' => $event->load('venue'),
        ]);
    }
}
