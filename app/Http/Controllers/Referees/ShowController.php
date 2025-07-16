<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Models\Referees\Referee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ShowController
{
    public function __invoke(Referee $referee): View
    {
        Gate::authorize('view', $referee);

        return view('referees.show', [
            'referee' => $referee,
        ]);
    }
}
