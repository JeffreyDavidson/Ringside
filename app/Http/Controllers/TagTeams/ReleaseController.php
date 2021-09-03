<?php

namespace App\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\ReleaseRequest;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

class ReleaseController extends Controller
{
    /**
     * Release a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\ReleaseRequest  $request
     * @param  \App\Repositories\TagTeamRepository $tagTeamRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(
        TagTeam $tagTeam,
        ReleaseRequest $request,
        TagTeamRepository $tagTeamRepository,
        WrestlerRepository $wrestlerRepository
    ) {
        throw_unless($tagTeam->canBeReleased(), new CannotBeReleasedException);

        $releaseDate = now()->toDateTimeString();

        if ($tagTeam->isSuspended()) {
            $tagTeamRepository->reinstate($tagTeam, $releaseDate);
            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $wrestlerRepository->reinstate($wrestler, $releaseDate);
            }
        }

        $tagTeamRepository->release($tagTeam, $releaseDate);
        $tagTeam->updateStatus()->save();

        foreach ($tagTeam->currentWrestlers as $wrestler) {
            $wrestlerRepository->release($wrestler, $releaseDate);
            $wrestler->updateStatus()->save();
        }

        return redirect()->route('tag-teams.index');
    }
}
