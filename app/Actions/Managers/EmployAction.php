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
     * @throws \App\Exceptions\CannotBeEmployedException
     */
    public function handle(Manager $manager, ?Carbon $startDate = null): void
    {
        throw_if($manager->isCurrentlyEmployed(), CannotBeEmployedException::class, $manager.' is currently employed and cannot be employed again.');

        $startDate ??= now();

        $this->managerRepository->employ($manager, $startDate);
    }
}
