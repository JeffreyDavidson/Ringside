<?php

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeam;
use App\Http\Controllers\Controller;

class SuspendController extends Controller
{
    /**
     * Suspend a tag team.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagteam)
    {
        $this->authorize('suspend', $tagteam);

        $tagteam->suspend();

        return redirect()->route('tagteams.index');
    }
}
