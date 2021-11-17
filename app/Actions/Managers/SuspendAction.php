<?php

namespace App\Actions\Managers;

use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Suspend a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return void
     */
    public function handle(Manager $manager): void
    {
        $suspensionDate = now()->toDateTimeString();

        $this->managerRepository->suspend($manager, $suspensionDate);
        $manager->updateStatus()->save();
    }
}
