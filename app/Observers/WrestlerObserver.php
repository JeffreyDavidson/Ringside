<?php

namespace App\Observers;

use App\Enums\WrestlerStatus;
use App\Models\Wrestler;

class WrestlerObserver
{
    /**
     * Handle the Wrestler "saving" event.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @return void
     */
    public function saving(Wrestler $wrestler)
    {
        if ($wrestler->isRetired()) {
            $wrestler->status = WrestlerStatus::RETIRED;
        } elseif ($wrestler->isInjured()) {
            $wrestler->status = WrestlerStatus::INJURED;
        } elseif ($wrestler->isSuspended()) {
            $wrestler->status = WrestlerStatus::SUSPENDED;
        } elseif ($wrestler->isBookable()) {
            $wrestler->status = WrestlerStatus::BOOKABLE;
        } elseif ($wrestler->isReleased()) {
            $wrestler->status = WrestlerStatus::RELEASED;
        } elseif ($wrestler->isPendingEmployment()) {
            $wrestler->status = WrestlerStatus::PENDING_EMPLOYMENT;
        } else {
            $wrestler->status = WrestlerStatus::UNEMPLOYED;
        }
    }
}
