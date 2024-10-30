<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Events\Managers\ManagerReleased;
use App\Exceptions\CannotBeReleasedException;
use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Release a manager.
     *
     * @throws \App\Exceptions\CannotBeReleasedException
     */
    public function handle(Manager $manager, ?Carbon $releaseDate = null): void
    {
        $this->ensureCanBeReleased($manager);

        $releaseDate ??= now();

        if ($manager->isSuspended()) {
            $this->managerRepository->reinstate($manager, $releaseDate);
        }

        if ($manager->isInjured()) {
            $this->managerRepository->clearInjury($manager, $releaseDate);
        }

        $this->managerRepository->release($manager, $releaseDate);

        event(new ManagerReleased($manager, $releaseDate));
    }

    /**
     * Ensure a manager can be released.
     *
     * @throws \App\Exceptions\CannotBeReleasedException
     */
    private function ensureCanBeReleased(Manager $manager): void
    {
        if ($manager->isUnemployed()) {
            throw CannotBeReleasedException::unemployed();
        }

        if ($manager->isReleased()) {
            throw CannotBeReleasedException::released();
        }

        if ($manager->hasFutureEmployment()) {
            throw CannotBeReleasedException::hasFutureEmployment();
        }

        if ($manager->isRetired()) {
            throw CannotBeReleasedException::retired();
        }
    }
}
