<?php

namespace App\Observers;

use App\Models\Wrestler;
use App\Enums\WrestlerStatus;

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
        if ($wrestler->is_bookable) {
            $wrestler->status = WrestlerStatus::BOOKABLE;
        } elseif ($wrestler->is_retired) {
            $wrestler->status = WrestlerStatus::RETIRED;
        } elseif ($wrestler->is_injured) {
            $wrestler->status =  WrestlerStatus::INJURED;
        } elseif ($wrestler->is_suspended) {
            $wrestler->status = WrestlerStatus::SUSPENDED;
        } else {
            $wrestler->status = WrestlerStatus::PENDING_INTRODUCTION;
        }
    }
}
