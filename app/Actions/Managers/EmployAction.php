<?php

namespace App\Actions\Managers;

use App\Models\Manager;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Employ a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Carbon\Carbon|null  $startDate
     * @return void
     */
    public function handle(Manager $manager, ?Carbon $startDate = null): void
    {
        $startDate ??= now();

        $this->managerRepository->employ($manager, $startDate);
        $manager->save();
    }
}
