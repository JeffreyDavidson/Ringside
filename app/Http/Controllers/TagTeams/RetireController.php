<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\RetireRequest;
use App\Models\TagTeam;
use App\Services\TagTeamService;

class RetireController extends Controller
{
    /**
     * Retire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\RetireRequest  $request
     * @param  \App\Services\TagTeamService $tagTeamService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, RetireRequest $request, TagTeamService $tagTeamService)
    {
        $tagTeamService->retire($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
