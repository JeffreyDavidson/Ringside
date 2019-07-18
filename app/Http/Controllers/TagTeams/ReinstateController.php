<?php

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeam;
use App\Http\Controllers\Controller;

class ReinstateController extends Controller
{
    /**
     * Reinstate a tag team.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagteam)
    {
        $this->authorize('reinstate', $tagteam);

        $tagteam->reinstate();

        return redirect()->route('tagteams.index');
    }
}
