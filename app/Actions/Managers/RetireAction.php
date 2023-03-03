<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Retire a manager.
     */
    public function handle(Manager $manager, ?Carbon $retirementDate = null): void
    {
        $this->ensureCanBeRetired($manager);

        $retirementDate ??= now();

        if ($manager->isSuspended()) {
            ReinstateAction::run($manager, $retirementDate);
        }

        if ($manager->isInjured()) {
            ClearInjuryAction::run($manager, $retirementDate);
        }

        ReleaseAction::run($manager, $retirementDate);

        $this->managerRepository->retire($manager, $retirementDate);

        $manager->currentTagTeams
            ->whenNotEmpty(fn () => RemoveFromCurrentTagTeamsAction::run($manager));

        $manager->currentWrestlers
            ->whenNotEmpty(fn () => RemoveFromCurrentWrestlersAction::run($manager));
    }

    /**
     * Ensure a manager can be retired.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    private function ensureCanBeRetired(Manager $manager): void
    {
        if ($manager->isUnemployed()) {
            throw CannotBeRetiredException::unemployed($manager);
        }

        if ($manager->hasFutureEmployment()) {
            throw CannotBeRetiredException::hasFutureEmployment($manager);
        }
    }
}
