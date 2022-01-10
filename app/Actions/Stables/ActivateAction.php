<?php

namespace App\Actions\Stables;

use App\Models\Stable;
use Lorisleiva\Actions\Concerns\AsAction;

class ActivateAction extends BaseStableAction
{
    use AsAction;

    /**
     * Activate a stable.
     *
     * @param  \App\Models\Stable  $stable
     *
     * @return void
     */
    public function handle(Stable $stable): void
    {
        $activationDate = now();

        if ($stable->currentWrestlers->isNotEmpty()) {
            foreach ($stable->currentWrestlers as $wrestler) {
                $this->wrestlerRepository->employ($wrestler, $activationDate);
                $wrestler->save();
            }
        }

        if ($stable->currentTagTeams->isNotEmpty()) {
            foreach ($stable->currentTagTeams as $tagTeam) {
                foreach ($tagTeam->currentWrestlers as $wrestler) {
                    $this->wrestlerRepository->employ($wrestler, $activationDate);
                    $wrestler->save();
                }
                $this->tagTeamRepository->employ($tagTeam, $activationDate);
                $tagTeam->save();
            }
        }

        $this->stableRepository->activate($stable, $activationDate);
        $stable->save();
    }
}
