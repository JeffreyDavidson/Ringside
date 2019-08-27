<?php

namespace App\Observers;

use App\Models\Referee;
use App\Enums\RefereeStatus;

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
        if ($referee->is_bookable) {
            $referee->status = RefereeStatus::BOOKABLE;
        } elseif ($referee->is_retired) {
            $referee->status = RefereeStatus::RETIRED;
        } elseif ($referee->is_injured) {
            $referee->status =  RefereeStatus::INJURED;
        } elseif ($referee->is_suspended) {
            $referee->status = RefereeStatus::SUSPENDED;
        } else {
            $referee->status = RefereeStatus::PENDING_INTRODUCTION;
        }
    }
}
