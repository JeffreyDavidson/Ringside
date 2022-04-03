<?php

namespace App\Actions\Managers;

use App\Models\Manager;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Reinstate a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Carbon\Carbon|null $reinstatementDate
     * @return void
     */
    public function handle(Manager $manager, ?Carbon $reinstatementDate = null): void
    {
        $reinstatementDate ??= now();

        $this->managerRepository->reinstate($manager, $reinstatementDate);
        $manager->save();
    }
}
