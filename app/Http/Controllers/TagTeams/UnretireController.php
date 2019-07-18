<?php

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeam;
use App\Http\Controllers\Controller;

class UnretireController extends Controller
{
    /**
     * Unretire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagteam)
    {
        $this->authorize('unretire', $tagteam);

        $tagteam->unretire();

        return redirect()->route('tagteams.index');
    }
}
