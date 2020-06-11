<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\EmployRequest;
use App\Models\TagTeam;

class EmployController extends Controller
{
    /**
     * Employ a tag team.
     *
     * @param  App\Models\TagTeam  $tagTeam
     * @param  App\Http\Requests\TagTeams\EmployReqeust  $request
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, EmployRequest $request)
    {
        $tagTeam->employ();

        return redirect()->route('tag-teams.index');
    }
}
