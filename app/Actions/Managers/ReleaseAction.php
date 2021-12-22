<?php

namespace App\Actions\Managers;

use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Release a manager.
     *
     * @param  \App\Models\Manager  $manager
     */
    public function handle(Manager $manager)
    {
        $releaseDate ??= now()->toDateTimeString();

        if ($manager->isSuspended()) {
            $this->managerRepository->reinstate($manager, $releaseDate);
        }

        if ($manager->isInjured()) {
            $this->managerRepository->clearInjury($manager, $releaseDate);
        }

        $this->managerRepository->release($manager, $releaseDate);
        $manager->save();

        if ($manager->has('currentTagTeams')) {
            $this->managerRepository->removeFromCurrentTagTeams($manager);
        }

        if ($manager->has('currentWrestlers')) {
            $this->managerRepository->removeFromCurrentWrestlers($manager);
        }
    }
}
