<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\TagTeams\RetireAction as TagTeamsRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Stable;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseStableAction
{
    use AsAction;

    /**
     * Retire a stable.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    public function handle(Stable $stable, Carbon $retirementDate = null): void
    {
        $this->ensureCanBeRetired($stable);

        $retirementDate ??= now();

        if ($stable->currentTagTeams->isNotEmpty()) {
            $stable->currentTagTeams
                ->each(fn ($tagTeam) => TagTeamsRetireAction::run($tagTeam, $retirementDate));
        }

        if ($stable->currentWrestlers->isNotEmpty()) {
            $stable->currentWrestlers
                ->each(fn ($wrestler) => WrestlersRetireAction::run($wrestler, $retirementDate));
        }

        $this->stableRepository->deactivate($stable, $retirementDate);
        $this->stableRepository->retire($stable, $retirementDate);
    }

    /**
     * Ensure a stable can be retired.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    private function ensureCanBeRetired(Stable $stable): void
    {
        if ($stable->isRetired()) {
            throw CannotBeRetiredException::retired($stable);
        }
    }
}
