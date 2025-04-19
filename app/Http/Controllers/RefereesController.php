<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Referee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class RefereesController
{
    /**
     * View a list of referees.
     */
    public function index(): View
    {
        Gate::authorize('viewList', Referee::class);

        return view('referees.index');
    }

    /**
     * Show the profile of a referee.
     */
    public function show(Referee $referee): View
    {
        Gate::authorize('view', $referee);

        return view('referees.show', [
            'referee' => $referee,
        ]);
    }
}
