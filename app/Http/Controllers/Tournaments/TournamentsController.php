<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Models\Tournaments\Tournament;
use Illuminate\Contracts\View\View;

class TournamentsController
{
    public function index(): View
    {
        return view('tournaments.index');
    }

    public function show(Tournament $tournament): View
    {
        return view('tournaments.show', ['tournament' => $tournament]);
    }
}
