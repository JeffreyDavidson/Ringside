<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeams\TagTeam;
use Illuminate\Contracts\View\View;

class TagTeamsController
{
    public function index(): View
    {
        return view('tag-teams.index');
    }

    public function show(TagTeam $tagTeam): View
    {
        return view('tag-teams.show', ['tagTeam' => $tagTeam]);
    }
}
