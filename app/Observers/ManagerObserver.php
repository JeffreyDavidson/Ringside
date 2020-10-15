<?php

namespace App\Observers;

use App\Enums\ManagerStatus;
use App\Models\Manager;

class ManagerObserver
{
    /**
     * Handle the Manager "saving" event.
     *
     * @param  \App\Models\Manager  $manager
     * @return void
     */
    public function saving(Manager $manager)
    {
        if ($manager->isCurrentlyEmployed()) {
            if ($manager->isInjured()) {
                $manager->status = ManagerStatus::INJURED;
            } elseif ($manager->isSuspended()) {
                $manager->status = ManagerStatus::SUSPENDED;
            } elseif ($manager->isAvailable()) {
                $manager->status = ManagerStatus::AVAILABLE;
            }
        } elseif ($manager->hasFutureEmployment()) {
            $manager->status = ManagerStatus::FUTURE_EMPLOYMENT;
        } elseif ($manager->isReleased()) {
            $manager->status = ManagerStatus::RELEASED;
        } elseif ($manager->isRetired()) {
            $manager->status = ManagerStatus::RETIRED;
        } else {
            $manager->status = ManagerStatus::UNEMPLOYED;
        }
    }
}
