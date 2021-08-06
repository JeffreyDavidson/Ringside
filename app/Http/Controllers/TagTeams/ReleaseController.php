<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\ReleaseRequest;
use App\Models\TagTeam;
use App\Services\TagTeamService;

class ReleaseController extends Controller
{
    /**
     * Release a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\ReleaseRequest  $request
     * @param  \App\Services\TagTeamService $tagTeamService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, ReleaseRequest $request, TagTeamService $tagTeamService)
    {
        $tagTeamService->release($tagTeam);

        return redirect()->route('tagTeams.index');
    }
}
