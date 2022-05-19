<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Reinstate a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon|null $reinstatementDate
     * @return void
     */
    public function handle(Manager $manager, ?Carbon $reinstatementDate = null): void
    {
        $reinstatementDate ??= now();

        $this->managerRepository->reinstate($manager, $reinstatementDate);
        $manager->save();
    }
}
