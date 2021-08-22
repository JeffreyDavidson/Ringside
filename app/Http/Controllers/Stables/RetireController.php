<?php

namespace App\Http\Controllers\Stables;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\RetireRequest;
use App\Models\Stable;
use App\Repositories\StableRepository;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

class RetireController extends Controller
{
    /**
     * Retire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\RetireRequest  $request
     * @param  \App\Repositories\StableRepository  $stableRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(
        Stable $stable,
        RetireRequest $request,
        StableRepository $stableRepository,
        TagTeamRepository $tagTeamRepository,
        WrestlerRepository $wrestlerRepository
    ) {
        throw_unless($stable->canBeRetired(), new CannotBeRetiredException);

        $retirementDate = now()->toDateTimeString();

        if ($stable->has('currentTagTeams')) {
            foreach ($stable->currentTagTeams as $tagTeam) {
                $tagTeamRepository->release($tagTeam, $retirementDate);
                $tagTeamRepository->retire($tagTeam, $retirementDate);
                $tagTeam->updateStatusAndSave();
            }
        }

        if ($stable->has('currentWrestlers')) {
            foreach ($stable->currentWrestlers as $wrestler) {
                $wrestlerRepository->release($wrestler, $retirementDate);
                $wrestlerRepository->retire($wrestler, $retirementDate);
                $wrestler->updateStatusAndSave();
            }
        }

        $stableRepository->deactivate($stable, $retirementDate);
        $stableRepository->retire($stable, $retirementDate);

        $stable->updateStatusAndSave();

        return redirect()->route('stables.index');
    }
}
