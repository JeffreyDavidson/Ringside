<?php

namespace App\Observers;

use App\Enums\RefereeStatus;
use App\Models\Referee;

class RefereeObserver
{
    /**
     * Handle the Referee "saving" event.
     *
     * @param  App\Models\Referee  $referee
     * @return void
     */
    public function saving(Referee $referee)
    {
        if ($referee->isRetired()) {
            $referee->status = RefereeStatus::RETIRED;
        } elseif ($referee->isInjured()) {
            $referee->status = RefereeStatus::INJURED;
        } elseif ($referee->isSuspended()) {
            $referee->status = RefereeStatus::SUSPENDED;
        } elseif ($referee->isBookable()) {
            $referee->status = RefereeStatus::BOOKABLE;
        } elseif ($referee->isPendingEmployment()) {
            $referee->status = RefereeStatus::PENDING_EMPLOYMENT;
        } else {
            $referee->status = RefereeStatus::UNEMPLOYED;
        }
    }
}
