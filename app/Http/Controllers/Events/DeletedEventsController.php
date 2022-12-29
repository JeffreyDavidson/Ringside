<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\View\View;

class DeletedEventsController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewList', Event::class);

        return view('events.deleted');
    }
}
