<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Retire a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return void
     */
    public function handle(Manager $manager): void
    {
        $retirementDate = now();

        if ($manager->isSuspended()) {
            ReinstateAction::run($manager, $retirementDate);
        }

        if ($manager->isInjured()) {
            ClearInjuryAction::run($manager, $retirementDate);
        }

        $this->managerRepository->release($manager, $retirementDate);
        $this->managerRepository->retire($manager, $retirementDate);
        $manager->save();

        $manager->currentTagTeams
            ->whenNotEmpty(fn () => $this->managerRepository->removeFromCurrentTagTeams($manager));

        $manager->currentWrestlers
            ->whenNotEmpty(fn () => $this->managerRepository->removeFromCurrentWrestlers($manager));
    }
}
