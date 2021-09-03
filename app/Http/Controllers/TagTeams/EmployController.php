<?php

namespace App\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\EmployRequest;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

class EmployController extends Controller
{
    /**
     * Employ a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Http\Requests\TagTeams\EmployReqeust  $request
     * @param  \App\Repositories\TagTeamRepository  $tagTeamRepository
     * @param  \App\Repositories\WrestlerRepository  $wrestlerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(TagTeam $tagTeam, EmployRequest $request, TagTeamRepository $tagTeamRepository, WrestlerRepository $wrestlerRepository)
    {
        throw_unless($tagTeam->canBeEmployed(), new CannotBeEmployedException);

        $employmentDate = now()->toDateTimeString();

        $tagTeamRepository->employ($tagTeam, $employmentDate);

        if ($tagTeam->currentWrestlers->every->isNotInEmployment()) {
            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $wrestlerRepository->employ($wrestler, $tagTeam->currentEmployment->started_at);
                $wrestler->updateStatus()->save();
            }
        }

        $tagTeam->updateStatus()->save();

        return redirect()->route('tag-teams.index');
    }
}
