<?php

namespace App\Observers;

use App\Enums\ManagerStatus;
use App\Models\Manager;

class ManagerObserver
{
    /**
     * Handle the Manager "saved" event.
     *
     * @param  \App\Models\Manager $manager
     *
     * @return void
     */
    public function saving(Manager $manager)
    {
        $manager->status = match (true) {
            $manager->isCurrentlyEmployed() => match (true) {
                $manager->isInjured() => ManagerStatus::injured(),
                $manager->isSuspended() => ManagerStatus::suspended(),
                default => ManagerStatus::available(),
            },
            $manager->hasFutureEmployment() => ManagerStatus::future_employment(),
            $manager->isReleased() => ManagerStatus::released(),
            $manager->isRetired() => ManagerStatus::retired(),
            default => ManagerStatus::unemployed()
        };
    }
}
