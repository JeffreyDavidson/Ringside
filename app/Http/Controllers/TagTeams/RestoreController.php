<?php

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeam;
use App\Http\Controllers\Controller;

class RestoreController extends Controller
{
    /**
     * Restore a deleted tag team.
     *
     * @param  int  $tagteamId
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke($tagteamId)
    {
        $tagteam = TagTeam::onlyTrashed()->findOrFail($tagteamId);

        $this->authorize('restore', $tagteam);

        $tagteam->restore();

        return redirect()->route('tagteams.index');
    }
}
