<?php

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeam;
use App\Http\Controllers\Controller;

class TagTeamRetirementsController extends Controller
{
    /**
     * Retire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(TagTeam $tagteam)
    {
        $this->authorize('retire', $tagteam);

        $tagteam->retire();

        return redirect()->route('tagteams.index', ['state' => 'retired']);
    }

    /**
     * Unretire a retired tagteam.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(TagTeam $tagteam)
    {
        $this->authorize('unretire', $tagteam);

        $tagteam->unretire();

        return redirect()->route('tagteams.index');
    }
}
