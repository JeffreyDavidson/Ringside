<?php

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Controller;
use App\Models\TagTeam;

class ReleaseController extends Controller
{
    /**
     * Release a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam)
    {
        $this->authorize('release', $tagTeam);

        throw_unless($tagTeam->canBeReleased(), new CannotBeReleasedException);

        ReleaseAction::run($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
