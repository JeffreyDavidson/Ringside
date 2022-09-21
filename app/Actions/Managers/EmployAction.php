<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\CannotBeEmployedException;
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
     *
     * @throws \App\Exceptions\CannotBeEmployedException
     */
    public function handle(Manager $manager, ?Carbon $startDate = null): void
    {
        throw_if($manager->canBeEmployed(), CannotBeEmployedException::class);

        $startDate ??= now();

        $this->managerRepository->employ($manager, $startDate);
    }
}
