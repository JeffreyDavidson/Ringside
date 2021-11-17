<?php

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\EmployRequest;
use App\Models\TagTeam;

class EmployController extends Controller
{
    /**
     * Employ a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\EmployReqeust  $request
     * @param  \App\Actions\TagTeams\EmployAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, EmployRequest $request, EmployAction $action)
    {
        throw_unless($tagTeam->canBeEmployed(), new CannotBeEmployedException);

        $action->handle($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
