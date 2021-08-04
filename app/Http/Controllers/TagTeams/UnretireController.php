<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\UnretireRequest;
use App\Models\TagTeam;
use App\Services\TagTeamService;

class UnretireController extends Controller
{
    /**
     * Unretire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\UnretireRequest  $request
     * @param  \App\Services\TagTeamService $tagTeamService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, UnretireRequest $request, TagTeamService $tagTeamService)
    {
        $tagTeamService->unretire($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
