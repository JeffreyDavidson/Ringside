<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Managers\RetireAction as ManagerRetireAction;
use App\Actions\TagTeams\RetireAction as TagTeamRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlerRetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Manager;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseStableAction
{
    use AsAction;

    /**
     * Retire a stable.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    public function handle(Stable $stable, ?Carbon $retirementDate = null): void
    {
        $this->ensureCanBeRetired($stable);

        $retirementDate ??= now();

        DB::transaction(function () use ($stable, $retirementDate) {
            if ($stable->isCurrentlyActivated()) {
                $this->stableRepository->deactivate($stable, $retirementDate);
            }

            if ($stable->currentTagTeams->isNotEmpty()) {
                $stable->currentTagTeams
                    ->each(fn (TagTeam $tagTeam) => TagTeamRetireAction::run($tagTeam, $retirementDate));
            }

            if ($stable->currentWrestlers->isNotEmpty()) {
                $stable->currentWrestlers
                    ->each(fn (Wrestler $wrestler) => WrestlerRetireAction::run($wrestler, $retirementDate));
            }

            if ($stable->currentManagers->isNotEmpty()) {
                $stable->currentManagers
                    ->each(fn (Manager $manager) => ManagerRetireAction::run($manager, $retirementDate));
            }

            $this->stableRepository->retire($stable, $retirementDate);
        });
    }

    /**
     * Ensure a stable can be retired.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    private function ensureCanBeRetired(Stable $stable): void
    {
        if ($stable->isUnactivated()) {
            throw CannotBeRetiredException::unactivated();
        }

        if ($stable->hasFutureActivation()) {
            throw CannotBeRetiredException::hasFutureActivation();
        }

        if ($stable->isRetired()) {
            throw CannotBeRetiredException::retired();
        }
    }
}
