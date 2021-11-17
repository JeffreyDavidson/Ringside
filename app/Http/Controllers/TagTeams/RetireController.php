<?php

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\RetireRequest;
use App\Models\TagTeam;

class RetireController extends Controller
{
    /**
     * Retire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\RetireRequest  $request
     * @param  \App\Actions\TagTeams\RetireAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, RetireRequest $request, RetireAction $action)
    {
        throw_unless($tagTeam->canBeRetired(), new CannotBeRetiredException);

        $action->handle($tagTeam);

        return redirect()->route('tag-teams.index');
    }
}
