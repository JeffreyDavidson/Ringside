<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Models\TagTeam;
use App\Services\TagTeamService;

class RestoreController extends Controller
{
    /**
     * Restore a deleted tag team.
     *
     * @param  int  $tagTeamId
     * @param  \App\Services\TagTeamService $tagTeamService
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke($tagTeamId, TagTeamService $tagTeamService)
    {
        $tagTeam = TagTeam::onlyTrashed()->findOrFail($tagTeamId);

        $this->authorize('restore', $tagTeam);

        $tagTeamService->restore($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
