<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\SuspendRequest;
use App\Models\TagTeam;

class SuspendController extends Controller
{
    /**
     * Suspend a tag team.
     *
     * @param  App\Models\TagTeam  $tagTeam
     * @param  App\Http\Requests\TagTeams\SuspendRequest  $request
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, SuspendRequest $request)
    {
        $tagTeam->suspend();

        return redirect()->route('tag-teams.index');
    }
}
