<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\SuspendRequest;
use App\Models\TagTeam;
use App\Services\TagTeamService;

class SuspendController extends Controller
{
    /**
     * Suspend a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\SuspendRequest  $request
     * @param  \App\Services\TagTeamService $tagTeamService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, SuspendRequest $request, TagTeamService $tagTeamService)
    {
        $tagTeamService->suspend($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
