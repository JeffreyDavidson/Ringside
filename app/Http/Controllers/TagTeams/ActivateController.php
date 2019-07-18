<?php

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeam;
use App\Http\Controllers\Controller;

class ActivateController extends Controller
{
    /**
     * Activate a pending introduced tag team.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagteam)
    {
        $this->authorize('activate', $tagteam);

        $tagteam->activate();

        return redirect()->route('tagteams.index');
    }
}
