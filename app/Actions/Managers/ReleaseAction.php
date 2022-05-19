<?php

declare(strict_types=1);

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
     * @return void
     */
    public function handle(Manager $manager): void
    {
        $releaseDate = now();

        if ($manager->isSuspended()) {
            ReinstateAction::run($manager, $releaseDate);
        }

        if ($manager->isInjured()) {
            ClearInjuryAction::run($manager, $releaseDate);
        }

        $this->managerRepository->release($manager, $releaseDate);
        $manager->save();

        if ($manager->currentTagTeams->isNotEmpty()) {
            $this->managerRepository->removeFromCurrentTagTeams($manager);
        }

        if ($manager->currentWrestlers->isNotEmpty()) {
            $this->managerRepository->removeFromCurrentWrestlers($manager);
        }
    }
}
