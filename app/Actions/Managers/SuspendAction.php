<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Suspend a manager.
     *
     *
     * @throws \App\Exceptions\CannotBeSuspendedException
     */
    public function handle(Manager $manager, ?Carbon $suspensionDate = null): void
    {
        throw_if($manager->isUnemployed(), CannotBeSuspendedException::class, $manager.' is unemployed and cannot be suspended.');
        throw_if($manager->isReleased(), CannotBeSuspendedException::class, $manager.' is released and cannot be suspended.');
        throw_if($manager->isRetired(), CannotBeSuspendedException::class, $manager.' is retired and cannot be suspended.');
        throw_if($manager->hasFutureEmployment(), CannotBeSuspendedException::class, $manager.' has not been officially employed and cannot be suspended.');
        throw_if($manager->isSuspended(), CannotBeSuspendedException::class, $manager.' is already suspended.');
        throw_if($manager->isInjured(), CannotBeSuspendedException::class, $manager.' is injured and cannot be suspended.');

        $suspensionDate ??= now();

        $this->managerRepository->suspend($manager, $suspensionDate);
    }
}
