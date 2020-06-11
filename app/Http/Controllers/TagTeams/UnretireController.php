<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\UnretireRequest;
use App\Models\TagTeam;

class UnretireController extends Controller
{
    /**
     * Unretire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\UnretireRequest  $request
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, UnretireRequest $request)
    {
        $tagTeam->unretire();

        return redirect()->route('tag-teams.index');
    }
}
