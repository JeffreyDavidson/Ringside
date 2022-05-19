<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Employ a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon|null  $startDate
     * @return void
     */
    public function handle(Manager $manager, ?Carbon $startDate = null): void
    {
        $startDate ??= now();

        $this->managerRepository->employ($manager, $startDate);
        $manager->save();
    }
}
