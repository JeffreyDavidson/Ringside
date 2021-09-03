<?php

namespace App\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\ReinstateRequest;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

class ReinstateController extends Controller
{
    /**
     * Reinstate a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\ReinstateRequest  $request
     * @param  \App\Repositories\TagTeamRepository $tagTeamRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(
        TagTeam $tagTeam,
        ReinstateRequest $request,
        TagTeamRepository $tagTeamRepository,
        WrestlerRepository $wrestlerRepository
    ) {
        throw_unless($tagTeam->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatementDate = now()->toDateTimeString();

        foreach ($tagTeam->currentWrestlers as $wrestler) {
            $wrestlerRepository->reinstate($wrestler, $reinstatementDate);
            $wrestlerRepository->employ($wrestler, $reinstatementDate);
            $wrestler->updateStatus()->save();
        }

        $tagTeamRepository->reinstate($tagTeam, $reinstatementDate);
        $tagTeam->updateStatus()->save();

        return redirect()->route('tag-teams.index');
    }
}
