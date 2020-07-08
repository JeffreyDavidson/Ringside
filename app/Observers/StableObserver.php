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
        } elseif ($stable->isActive()) {
            $stable->status = StableStatus::ACTIVE;
        } elseif ($stable->isDeactivated()) {
            $stable->status = StableStatus::INACTIVE;
        } elseif ($stable->hasFutureActivation()) {
            $stable->status = StableStatus::FUTURE_ACTIVATION;
        } else {
            $stable->status = StableStatus::UNACTIVATED;
        }
    }
}
