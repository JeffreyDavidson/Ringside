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
                $manager->isInjured() => ManagerStatus::Injured,
                $manager->isSuspended() => ManagerStatus::Suspended,
                default => ManagerStatus::Available,
            },
            $manager->hasFutureEmployment() => ManagerStatus::FutureEmployment,
            $manager->isReleased() => ManagerStatus::Released,
            $manager->isRetired() => ManagerStatus::Retired,
            default => ManagerStatus::Unemployed
        };
    }
}
