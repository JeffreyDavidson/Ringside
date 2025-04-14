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

final class RetireAction extends BaseStableAction
{
    use AsAction;

    /**
     * Retire a stable.
     *
     * @throws CannotBeRetiredException
     */
    public function handle(Stable $stable, ?Carbon $retirementDate = null): void
    {
        $this->ensureCanBeRetired($stable);

        $retirementDate ??= now();

        DB::transaction(function () use ($stable, $retirementDate): void {
            if ($stable->isCurrentlyActivated()) {
                $this->stableRepository->deactivate($stable, $retirementDate);
            }

            if ($stable->currentTagTeams->isNotEmpty()) {
                $stable->currentTagTeams
                    ->each(fn (TagTeam $tagTeam) => resolve(TagTeamRetireAction::class)->handle($tagTeam, $retirementDate));
            }

            if ($stable->currentWrestlers->isNotEmpty()) {
                $stable->currentWrestlers
                    ->each(fn (Wrestler $wrestler) => resolve(WrestlerRetireAction::class)->handle($wrestler, $retirementDate));
            }

            if ($stable->currentManagers->isNotEmpty()) {
                $stable->currentManagers
                    ->each(fn (Manager $manager) => resolve(ManagerRetireAction::class)->handle($manager, $retirementDate));
            }

            $this->stableRepository->retire($stable, $retirementDate);
        });
    }

    /**
     * Ensure a stable can be retired.
     *
     * @throws CannotBeRetiredException
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
