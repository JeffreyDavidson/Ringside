<?php

namespace App\Actions\Stables;

use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseStableAction
{
    use AsAction;

    /**
     * Retire a stable.
     *
     * @param  \App\Models\Stable  $stable
     *
     * @return void
     */
    public function handle(Stable $stable): void
    {
        $retirementDate = now();

        if ($stable->currentTagTeams->isNotEmpty()) {
            $stable->currentTagTeams->each(function (TagTeam $tagTeam) use ($retirementDate) {
                $this->tagTeamRepository->release($tagTeam, $retirementDate);
                $this->tagTeamRepository->retire($tagTeam, $retirementDate);
                $tagTeam->save();
            });
        }

        if ($stable->currentWrestlers->isNotEmpty()) {
            $stable->currentWrestlers->each(function (Wrestler $wrestler) use ($retirementDate) {
                $this->wrestlerRepository->release($wrestler, $retirementDate);
                $this->wrestlerRepository->retire($wrestler, $retirementDate);
                $wrestler->save();
            });
        }

        $this->stableRepository->deactivate($stable, $retirementDate);
        $this->stableRepository->retire($stable, $retirementDate);
        $stable->save();
    }
}
