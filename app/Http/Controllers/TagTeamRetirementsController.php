<?php

namespace App\Http\Controllers;

use App\TagTeam;

class TagTeamRetirementsController extends Controller
{
    /**
     * Retire a tag team.
     *
     * @param  \App\TagTeam  $tagteam
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
     * @param  \App\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(TagTeam $tagteam)
    {
        $this->authorize('unretire', $tagteam);

        $tagteam->unretire();

        return redirect()->route('tagteams.index');
    }
}
