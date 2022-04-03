<?php

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Models\TagTeam;

class ReinstateController extends Controller
{
    /**
     * Reinstate a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam)
    {
        $this->authorize('reinstate', $tagTeam);

        throw_unless($tagTeam->canBeReinstated(), CannotBeReinstatedException::class);

        ReinstateAction::run($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
