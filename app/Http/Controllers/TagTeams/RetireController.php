<?php

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Models\TagTeam;

class RetireController extends Controller
{
    /**
     * Retire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam)
    {
        $this->authorize('retire', $tagTeam);

        throw_unless($tagTeam->canBeRetired(), new CannotBeRetiredException);

        RetireAction::run($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
