<?php

namespace App\Actions\Managers;

use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Reinstate a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return void
     */
    public function handle(Manager $manager): void
    {
        $reinstatementDate = now()->toDateTimeString();

        $this->managerRepository->reinstate($manager, $reinstatementDate);
        $manager->save();
    }
}
