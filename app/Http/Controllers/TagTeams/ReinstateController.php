<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\ReinstateRequest;
use App\Models\TagTeam;
use App\Services\TagTeamService;

class ReinstateController extends Controller
{
    /**
     * Reinstate a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\ReinstateRequest  $request
     * @param  \App\Services\TagTeamService $tagTeamService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, ReinstateRequest $request, TagTeamService $tagTeamService)
    {
        $tagTeamService->reinstate($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
