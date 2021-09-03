<?php

namespace App\Http\Controllers\Stables;

use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\ActivateRequest;
use App\Models\Stable;
use App\Repositories\StableRepository;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

class ActivateController extends Controller
{
    /**
     * Activate a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\ActivateRequest  $stable
     * @param  \App\Repositories\StableRepository  $stableRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(
        Stable $stable,
        ActivateRequest $request,
        StableRepository $stableRepository,
        WrestlerRepository $wrestlerRepository,
        TagTeamRepository $tagTeamRepository
    ) {
        throw_unless($stable->canBeActivated(), new CannotBeActivatedException);

        $activationDate = now()->toDateTimeString();

        if ($stable->currentWrestlers->every->isNotInEmployment()) {
            foreach ($stable->currentWrestlers as $wrestler) {
                $wrestlerRepository->employ($wrestler, $activationDate);
                $wrestler->updateStatus()->save();
            }
        }

        if ($stable->currentTagTeams->every->isNotInEmployment()) {
            foreach ($stable->currentTagTeams as $tagTeam) {
                foreach ($tagTeam->currentWrestlers as $wrestler) {
                    $wrestlerRepository->employ($wrestler, $activationDate);
                    $wrestler->updateStatus()->save();
                }
                $tagTeamRepository->employ($tagTeam, $activationDate);
                $tagTeam->updateStatus()->save();
            }
        }

        $stableRepository->activate($stable, $activationDate);
        $stable->updateStatus()->save();

        return redirect()->route('stables.index');
    }
}
