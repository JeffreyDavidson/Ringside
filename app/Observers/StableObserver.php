<?php

namespace App\Observers;

use App\Enums\StableStatus;
use App\Models\Stable;

class StableObserver
{
    /**
     * Handle the Stable "saving" event.
     *
     * @param  \App\Models\Stable $stable
     * @return void
     */
    public function saving(Stable $stable)
    {
        if ($stable->isRetired()) {
            $stable->status = StableStatus::RETIRED;
        } elseif ($stable->isCurrentlyActive()) {
            $stable->status = StableStatus::ACTIVE;
        } elseif ($stable->hasFutureActivation()) {
            $stable->status = StableStatus::FUTURE_ACTIVATION;
        } elseif ($stable->isDeactivated()) {
            $stable->status = StableStatus::INACTIVE;
        } else {
            $stable->status = StableStatus::UNACTIVATED;
        }
    }
}
