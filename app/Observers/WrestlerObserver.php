<?php

namespace App\Observers;

use App\Enums\WrestlerStatus;
use App\Models\Wrestler;

class WrestlerObserver
{
    /**
     * Handle the Wrestler "saving" event.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return void
     */
    public function saving(Wrestler $wrestler)
    {
        if ($wrestler->isCurrentlyEmployed()) {
            if ($wrestler->isInjured()) {
                $wrestler->status = WrestlerStatus::INJURED;
            } elseif ($wrestler->isSuspended()) {
                $wrestler->status = WrestlerStatus::SUSPENDED;
            } elseif ($wrestler->isBookable()) {
                $wrestler->status = WrestlerStatus::BOOKABLE;
            }
        } elseif ($wrestler->hasFutureEmployment()) {
            $wrestler->status = WrestlerStatus::FUTURE_EMPLOYMENT;
        } elseif ($wrestler->isReleased()) {
            $wrestler->status = WrestlerStatus::RELEASED;
        } elseif ($wrestler->isRetired()) {
            $wrestler->status = WrestlerStatus::RETIRED;
        } else {
            $wrestler->status = WrestlerStatus::UNEMPLOYED;
        }
    }
}
