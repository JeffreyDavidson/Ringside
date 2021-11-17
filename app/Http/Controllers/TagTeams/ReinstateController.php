<?php

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\ReinstateRequest;
use App\Models\TagTeam;

class ReinstateController extends Controller
{
    /**
     * Reinstate a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\ReinstateRequest  $request
     * @param  \App\Actions\TagTeams\ReinstateAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, ReinstateRequest $request, ReinstateAction $action)
    {
        throw_unless($tagTeam->canBeReinstated(), new CannotBeReinstatedException);

        $action->handle($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
