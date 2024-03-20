<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class DeletedEventsController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewList', Event::class);

        return view('events.deleted');
    }
}
