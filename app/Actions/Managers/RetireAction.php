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
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    public function handle(Manager $manager, ?Carbon $retirementDate = null): void
    {
        throw_if($manager->isUnemployed(), CannotBeRetiredException::class, $manager.' is unemployed and cannot be retired.');
        throw_if($manager->hasFutureEmployment(), CannotBeRetiredException::class, $manager.' has not been officially employed and cannot be retired');
        throw_if($manager->isRetired(), CannotBeRetiredException::class, $manager.' is already retired.');
        throw_if($manager->isReleased(), CannotBeRetiredException::class, $manager.' was released and cannot be retired. Re-employ this manager to retire them.');

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
}
