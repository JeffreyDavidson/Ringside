<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\CannotBeReinstatedException;
use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Reinstate a manager.
     *
     * @throws \App\Exceptions\CannotBeReinstatedException
     */
    public function handle(Manager $manager, ?Carbon $reinstatementDate = null): void
    {
        $this->ensureCanBeReinstated($manager);

        $reinstatementDate ??= now();

        $this->managerRepository->reinstate($manager, $reinstatementDate);
    }

    /**
     * Ensure a manager can be reinstated.
     *
     * @throws \App\Exceptions\CannotBeReinstatedException
     */
    private function ensureCanBeReinstated(Manager $manager): void
    {
        if ($manager->isUnemployed()) {
            throw CannotBeReinstatedException::unemployed();
        }

        if ($manager->isReleased()) {
            throw CannotBeReinstatedException::released();
        }

        if ($manager->hasFutureEmployment()) {
            throw CannotBeReinstatedException::hasFutureEmployment();
        }

        if ($manager->isInjured()) {
            throw CannotBeReinstatedException::injured();
        }

        if ($manager->isRetired()) {
            throw CannotBeReinstatedException::retired();
        }

        if ($manager->isAvailable()) {
            throw CannotBeReinstatedException::available();
        }
    }
}
