<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Models\Referees\Referee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class IndexController
{
    public function __invoke(): View
    {
        Gate::authorize('viewList', Referee::class);

        return view('referees.index');
    }
}
