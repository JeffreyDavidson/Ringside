<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\RetireRequest;
use App\Models\TagTeam;

class RetireController extends Controller
{
    /**
     * Retire a tag team.
     *
     * @param  App\Models\TagTeam  $tagTeam
     * @param  App\Http\Requests\TagTeams\RetireRequest  $request
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, RetireRequest $request)
    {
        $tagTeam->retire();

        return redirect()->route('tag-teams.index');
    }
}
