<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Models\Events\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class IndexController
{
    public function __invoke(): View
    {
        Gate::authorize('viewList', Event::class);

        return view('events.index');
    }
}