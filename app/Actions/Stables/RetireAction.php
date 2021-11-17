<?php

namespace App\Actions\Stables;

use App\Models\Stable;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseStableAction
{
    use AsAction;

    /**
     * Retire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return void
     */
    public function handle(Stable $stable): void
    {
        $retirementDate = now()->toDateTimeString();

        if ($stable->has('currentTagTeams')) {
            foreach ($stable->currentTagTeams as $tagTeam) {
                $this->tagTeamRepository->release($tagTeam, $retirementDate);
                $this->tagTeamRepository->retire($tagTeam, $retirementDate);
                $tagTeam->updateStatus()->save();
            }
        }

        if ($stable->has('currentWrestlers')) {
            foreach ($stable->currentWrestlers as $wrestler) {
                $this->wrestlerRepository->release($wrestler, $retirementDate);
                $this->wrestlerRepository->retire($wrestler, $retirementDate);
                $wrestler->updateStatus()->save();
            }
        }

        $this->stableRepository->deactivate($stable, $retirementDate);
        $this->stableRepository->retire($stable, $retirementDate);
        $stable->updateStatus()->save();
    }
}
