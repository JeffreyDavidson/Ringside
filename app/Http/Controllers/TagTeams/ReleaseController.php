<?php

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\ReleaseRequest;
use App\Models\TagTeam;

class ReleaseController extends Controller
{
    /**
     * Release a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\ReleaseRequest  $request
     * @param  \App\Actions\TagTeams\ReleaseAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, ReleaseRequest $request, ReleaseAction $action)
    {
        throw_unless($tagTeam->canBeReleased(), new CannotBeReleasedException);

        $action->handle($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
