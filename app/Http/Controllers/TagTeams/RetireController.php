<?php

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeam;
use App\Http\Controllers\Controller;

class RetireController extends Controller
{
    /**
     * Retire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagteam)
    {
        $this->authorize('retire', $tagteam);

        $tagteam->retire();

        return redirect()->route('tagteams.index');
    }
}
