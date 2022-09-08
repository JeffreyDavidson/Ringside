<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\SuspendAction;
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

        SuspendAction::run($tagTeam);

        return to_route('tag-teams.index');
    }
}
