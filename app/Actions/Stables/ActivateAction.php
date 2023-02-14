<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\TagTeams\EmployAction as TagTeamEmployAction;
use App\Actions\Wrestlers\EmployAction as WrestlerEmployAction;
use App\Exceptions\CannotBeActivatedException;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ActivateAction extends BaseStableAction
{
    use AsAction;

    /**
     * Activate a stable.
     *
     *
     * @throws \App\Exceptions\CannotBeActivatedException
     */
    public function handle(Stable $stable, ?Carbon $startDate = null): void
    {
        throw_if($stable->canBeActivated(), CannotBeActivatedException::class);

        $startDate ??= now();

        if ($stable->currentWrestlers->isNotEmpty()) {
            $stable->currentWrestlers->each(
                fn (Wrestler $wrestler) => WrestlerEmployAction::run($wrestler, $startDate)
            );
        }

        if ($stable->currentTagTeams->isNotEmpty()) {
            $stable->currentTagTeams->each(
                fn (TagTeam $tagTeam) => TagTeamEmployAction::run($tagTeam, $startDate)
            );
        }

        $this->stableRepository->activate($stable, $startDate);
    }
}
