<?php

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeam;
use App\Http\Controllers\Controller;

class TagTeamSuspensionsController extends Controller
{
    /**
     * Suspend a tag team.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(TagTeam $tagteam)
    {
        $this->authorize('suspend', $tagteam);

        $tagteam->suspend();

        return redirect()->route('tagteams.index', ['state' => 'suspended']);
    }

    /**
     * Reinstate a suspended tag team.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(TagTeam $tagteam)
    {
        $this->authorize('reinstate', $tagteam);

        $tagteam->reinstate();

        return redirect()->route('tagteams.index');
    }
}
