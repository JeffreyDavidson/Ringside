<?php

declare(strict_types=1);

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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam)
    {
        $this->authorize('release', $tagTeam);

        throw_unless($tagTeam->canBeReleased(), CannotBeReleasedException::class);

        ReleaseAction::run($tagTeam);

        return to_route('tag-teams.index');
    }
}
