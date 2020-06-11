<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\ReinstateRequest;
use App\Models\TagTeam;

class ReinstateController extends Controller
{
    /**
     * Reinstate a tag team.
     *
     * @param  App\Models\TagTeam  $tagTeam
     * @param  App\Http\Requests\TagTeams\ReinstateRequest  $request
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, ReinstateRequest $request)
    {
        $tagTeam->reinstate();

        return redirect()->route('tag-teams.index');
    }
}
