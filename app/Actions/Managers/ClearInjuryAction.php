<?php

namespace App\Actions\Managers;

use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

class ClearInjuryAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Clear an injury of a manager.
     *
     * @param  \App\Models\Manager  $manager
     *
     * @return void
     */
    public function handle(Manager $manager): void
    {
        $this->managerRepository->clearInjury($manager, now());
        $manager->save();
    }
}
