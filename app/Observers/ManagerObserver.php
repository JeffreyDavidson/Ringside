<?php

namespace App\Observers;

use App\Enums\ManagerStatus;
use App\Models\Manager;

class ManagerObserver
{
    /**
     * Handle the Manager "saving" event.
     *
     * @param  App\Models\Manager  $manager
     * @return void
     */
    public function saving(Manager $manager)
    {
        if ($manager->isRetired()) {
            $manager->status = ManagerStatus::RETIRED;
        } elseif ($manager->isInjured()) {
            $manager->status = ManagerStatus::INJURED;
        } elseif ($manager->isSuspended()) {
            $manager->status = ManagerStatus::SUSPENDED;
        } elseif ($manager->isAvailable()) {
            $manager->status = ManagerStatus::AVAILABLE;
        } elseif ($manager->isReleased()) {
            $manager->status = ManagerStatus::RELEASED;
        } elseif ($manager->hasFutureEmployment()) {
            $manager->status = ManagerStatus::FUTURE_EMPLOYMENT;
        } else {
            $manager->status = ManagerStatus::UNEMPLOYED;
        }
    }
}
