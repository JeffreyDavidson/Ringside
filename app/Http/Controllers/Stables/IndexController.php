<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Models\Stables\Stable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class IndexController
{
    public function __invoke(): View
    {
        Gate::authorize('viewList', Stable::class);

        return view('stables.index');
    }
}