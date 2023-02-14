<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ManagerStatus;
use App\Models\Manager;

class ManagerObserver
{
    /**
     * Handle the Manager "saved" event.
     */
    public function saving(Manager $manager): void
    {
        $manager->status = match (true) {
            $manager->isCurrentlyEmployed() => match (true) {
                $manager->isInjured() => ManagerStatus::INJURED,
                $manager->isSuspended() => ManagerStatus::SUSPENDED,
                default => ManagerStatus::AVAILABLE,
            },
            $manager->hasFutureEmployment() => ManagerStatus::FUTURE_EMPLOYMENT,
            $manager->isReleased() => ManagerStatus::RELEASED,
            $manager->isRetired() => ManagerStatus::RETIRED,
            default => ManagerStatus::UNEMPLOYED
        };
    }
}
