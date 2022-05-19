<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\TagTeams\RetireAction as TagTeamsRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
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
     * @return void
     */
    public function handle(Stable $stable): void
    {
        $retirementDate = now();

        if ($stable->currentTagTeams->isNotEmpty()) {
            $stable->currentTagTeams->each(function (TagTeam $tagTeam) use ($retirementDate) {
                TagTeamsRetireAction::run($tagTeam, $retirementDate);
            });
        }

        if ($stable->currentWrestlers->isNotEmpty()) {
            $stable->currentWrestlers->each(function (Wrestler $wrestler) use ($retirementDate) {
                WrestlersRetireAction::run($wrestler, $retirementDate);
            });
        }

        $this->stableRepository->deactivate($stable, $retirementDate);
        $this->stableRepository->retire($stable, $retirementDate);
        $stable->save();
    }
}
