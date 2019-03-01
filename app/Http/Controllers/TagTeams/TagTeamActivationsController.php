<?php

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeam;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TagTeamActivationsController extends Controller
{
    /**
     * Activate and inactive tag team.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(TagTeam $tagteam)
    {
        $this->authorize('activate', $tagteam);

        $tagteam->activate();

        return redirect()->route('tagteams.index');
    }

    /**
     * Deactivate an active tag team.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(TagTeam $tagteam)
    {
        $this->authorize('deactivate', $tagteam);

        $tagteam->deactivate();

        return redirect()->route('tagteams.index', ['state' => 'inactive']);
    }
}
