<?php

namespace App\Actions\Managers;

use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Employ a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return void
     */
    public function handle(Manager $manager): void
    {
        $employmentDate = now()->toDateTimeString();

        $this->managerRepository->employ($manager, $employmentDate);
        $manager->updateStatus()->save();
    }
}
