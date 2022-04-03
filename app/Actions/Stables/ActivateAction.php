<?php

namespace App\Actions\Stables;

use App\Actions\TagTeams\EmployAction as TagTeamEmployAction;
use App\Actions\Wrestlers\EmployAction as WrestlerEmployAction;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ActivateAction extends BaseStableAction
{
    use AsAction;

    /**
     * Activate a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Carbon\Carbon|null  $activationDate
     * @return void
     */
    public function handle(Stable $stable, ?Carbon $activationDate = null): void
    {
        $activationDate ??= now();

        if ($stable->currentWrestlers->isNotEmpty()) {
            $stable->currentWrestlers->each(
                fn (Wrestler $wrestler) => WrestlerEmployAction::run($wrestler, $activationDate)
            );
        }

        if ($stable->currentTagTeams->isNotEmpty()) {
            $stable->currentTagTeams->each(
                fn (TagTeam $tagTeam) => TagTeamEmployAction::run($tagTeam, $activationDate)
            );
        }

        $this->stableRepository->activate($stable, $activationDate);
        $stable->save();
    }
}
