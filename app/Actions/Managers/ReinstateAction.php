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
            throw CannotBeReinstatedException::unemployed($manager);
        }

        if ($manager->isReleased()) {
            throw CannotBeReinstatedException::released($manager);
        }

        if ($manager->hasFutureEmployment()) {
            throw CannotBeReinstatedException::hasFutureEmployment($manager);
        }

        if ($manager->isInjured()) {
            throw CannotBeReinstatedException::injured($manager);
        }

        if ($manager->isRetired()) {
            throw CannotBeReinstatedException::retired($manager);
        }

        if ($manager->isBookable()) {
            throw CannotBeReinstatedException::bookable($manager);
        }
    }
}
