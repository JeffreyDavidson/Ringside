<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Models\Events\Event;
use Illuminate\Contracts\View\View;

class EventBudgetsController
{
    public function show(Event $event): View
    {
        return view('events.budget', ['event' => $event]);
    }
}
