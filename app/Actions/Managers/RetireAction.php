<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Events\Managers\ManagerRetired;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Retire a manager.
     */
    public function handle(Manager $manager, ?Carbon $retirementDate = null): void
    {
        $this->ensureCanBeRetired($manager);

        $retirementDate ??= now();

        if ($manager->isSuspended()) {
            $this->managerRepository->reinstate($manager, $retirementDate);
        }

        if ($manager->isInjured()) {
            $this->managerRepository->clearInjury($manager, $retirementDate);
        }

        if ($manager->isCurrentlyEmployed()) {
            $this->managerRepository->release($manager, $retirementDate);
        }

        $this->managerRepository->retire($manager, $retirementDate);

        event(new ManagerRetired($manager, $retirementDate));
    }

    /**
     * Ensure a manager can be retired.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    private function ensureCanBeRetired(Manager $manager): void
    {
        if ($manager->isUnemployed()) {
            throw CannotBeRetiredException::unemployed($manager);
        }

        if ($manager->hasFutureEmployment()) {
            throw CannotBeRetiredException::hasFutureEmployment($manager);
        }

        if ($manager->isRetired()) {
            throw CannotBeRetiredException::retired($manager);
        }
    }
}
