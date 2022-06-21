<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\TagTeams\RetireAction as TagTeamsRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseStableAction
{
    use AsAction;

    /**
     * Retire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Support\Carbon|null  $retirementDate
     * @return void
     */
    public function handle(Stable $stable, ?Carbon $retirementDate = null): void
    {
        $retirementDate ??= now();

        if ($stable->currentTagTeams->isNotEmpty()) {
            $stable->currentTagTeams
                ->each(fn (TagTeam $tagTeam) => TagTeamsRetireAction::run($tagTeam, $retirementDate));
        }

        if ($stable->currentWrestlers->isNotEmpty()) {
            $stable->currentWrestlers
                ->each(fn (Wrestler $wrestler) => WrestlersRetireAction::run($wrestler, $retirementDate));
        }

        $this->stableRepository->deactivate($stable, $retirementDate);
        $this->stableRepository->retire($stable, $retirementDate);
    }
}
