<?php

namespace App\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\RetireRequest;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

class RetireController extends Controller
{
    /**
     * Retire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\RetireRequest  $request
     * @param  \App\Repositories\TagTeamRepository  $tagTeamRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(
        TagTeam $tagTeam,
        RetireRequest $request,
        TagTeamRepository $tagTeamRepository,
        WrestlerRepository $wrestlerRepository
    ) {
        throw_unless($tagTeam->canBeRetired(), new CannotBeRetiredException);

        $retirementDate = now()->toDateTimeString();

        if ($tagTeam->isSuspended()) {
            $tagTeamRepository->reinstate($tagTeam, $retirementDate);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $wrestlerRepository->reinstate($wrestler, $retirementDate);
            }
        }

        foreach ($tagTeam->currentWrestlers as $wrestler) {
            $wrestlerRepository->release($wrestler, $retirementDate);
            $wrestlerRepository->retire($wrestler, $retirementDate);
            $wrestler->updateStatus()->save();
        }

        $tagTeamRepository->release($tagTeam, $retirementDate);
        $tagTeamRepository->retire($tagTeam, $retirementDate);
        $tagTeam->updateStatus()->save();

        return redirect()->route('tag-teams.index');
    }
}
