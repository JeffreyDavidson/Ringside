<?php

declare(strict_types=1);

namespace App\Http\Controllers\Matches;

use App\Models\Matches\EventMatch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class IndexController
{
    public function __invoke(): View
    {
        Gate::authorize('viewList', EventMatch::class);

        return view('matches.index');
    }
}