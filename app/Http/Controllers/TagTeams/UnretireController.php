<?php

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Models\TagTeam;

class UnretireController extends Controller
{
    /**
     * Unretire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam)
    {
        $this->authorize('unretire', $tagTeam);

        throw_unless($tagTeam->canBeUnretired(), (new CannotBeUnretiredException));

        UnretireAction::run($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
