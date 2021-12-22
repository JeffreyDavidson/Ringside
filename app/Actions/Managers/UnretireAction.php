<?php

namespace App\Actions\Managers;

use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Unretire a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return void
     */
    public function handle(Manager $manager): void
    {
        $unretiredDate = now()->toDateTimeString();

        $this->managerRepository->unretire($manager, $unretiredDate);
        $this->managerRepository->employ($manager, $unretiredDate);
        $manager->save();
    }
}
