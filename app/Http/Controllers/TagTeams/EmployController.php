<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\EmployRequest;
use App\Models\TagTeam;
use App\Services\TagTeamService;

class EmployController extends Controller
{
    /**
     * Employ a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\EmployReqeust  $request
     * @param  \App\Services\TagTeamService $tagTeamService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, EmployRequest $request, TagTeamService $tagTeamService)
    {
        $tagTeamService->employ($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
