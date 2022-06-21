<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Retire a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon|null  $retirementDate
     * @return void
     */
    public function handle(Manager $manager, ?Carbon $retirementDate = null): void
    {
        $retirementDate ??= now();

        if ($manager->isSuspended()) {
            ReinstateAction::run($manager, $retirementDate);
        }

        if ($manager->isInjured()) {
            ClearInjuryAction::run($manager, $retirementDate);
        }

        $this->managerRepository->release($manager, $retirementDate);
        $this->managerRepository->retire($manager, $retirementDate);

        $manager->currentTagTeams
            ->whenNotEmpty(fn () => $this->managerRepository->removeFromCurrentTagTeams($manager));

        $manager->currentWrestlers
            ->whenNotEmpty(fn () => $this->managerRepository->removeFromCurrentWrestlers($manager));
    }
}
