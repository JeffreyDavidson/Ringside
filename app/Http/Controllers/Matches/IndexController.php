<?php

declare(strict_types=1);

namespace App\Http\Controllers\Matches;

use App\Models\Events\Event;
use Illuminate\Contracts\View\View;

class IndexController
{
    public function __invoke(Event $event): View
    {
        return view('matches.index', [
            'event' => $event,
        ]);
    }
}
