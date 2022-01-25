<?php

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
     *
     * @return void
     */
    public function handle(Manager $manager): void
    {
        $retirementDate = now();

        if ($manager->isSuspended()) {
            $this->managerRepository->reinstate($manager, $retirementDate);
        }

        if ($manager->isInjured()) {
            $this->managerRepository->clearInjury($manager, $retirementDate);
        }

        $this->managerRepository->release($manager, $retirementDate);
        $this->managerRepository->retire($manager, $retirementDate);
        $manager->save();

        if ($manager->currentTagTeams->isNotEmpty()) {
            $this->managerRepository->removeFromCurrentTagTeams($manager);
        }

        if ($manager->currentWrestlers->isNotEmpty()) {
            $this->managerRepository->removeFromCurrentWrestlers($manager);
        }
    }
}
