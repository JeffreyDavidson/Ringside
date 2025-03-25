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
     * @throws \App\Exceptions\CannotBeSuspendedException
     */
    public function handle(Manager $manager, ?Carbon $suspensionDate = null): void
    {
        $this->ensureCanBeSuspended($manager);

        $suspensionDate ??= now();

        $this->managerRepository->suspend($manager, $suspensionDate);
    }

    /**
     * Ensure a manager can be suspended.
     *
     * @throws \App\Exceptions\CannotBeSuspendedException
     */
    private function ensureCanBeSuspended(Manager $manager): void
    {
        if ($manager->isUnemployed()) {
            throw CannotBeSuspendedException::unemployed();
        }

        if ($manager->isReleased()) {
            throw CannotBeSuspendedException::released();
        }

        if ($manager->isRetired()) {
            throw CannotBeSuspendedException::retired();
        }

        if ($manager->hasFutureEmployment()) {
            throw CannotBeSuspendedException::hasFutureEmployment();
        }

        if ($manager->isSuspended()) {
            throw CannotBeSuspendedException::suspended();
        }

        if ($manager->isInjured()) {
            throw CannotBeSuspendedException::injured();
        }
    }
}
