<?php

namespace App\Observers;

use App\Models\Manager;
use App\Enums\ManagerStatus;

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
        if ($manager->is_bookable) {
            $manager->status = ManagerStatus::BOOKABLE;
        } elseif ($manager->is_retired) {
            $manager->status = ManagerStatus::RETIRED;
        } elseif ($manager->is_injured) {
            $manager->status =  ManagerStatus::INJURED;
        } elseif ($manager->is_suspended) {
            $manager->status = ManagerStatus::SUSPENDED;
        } else {
            $manager->status = ManagerStatus::PENDING_INTRODUCTION;
        }
    }
}
