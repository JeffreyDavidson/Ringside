<?php

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Controller;
use App\Models\TagTeam;

class SuspendController extends Controller
{
    /**
     * Suspend a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam)
    {
        $this->authorize('suspend', $tagTeam);

        throw_unless($tagTeam->canBeSuspended(), CannotBeSuspendedException::class);

        SuspendAction::run($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
