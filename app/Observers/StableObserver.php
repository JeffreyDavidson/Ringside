<?php

namespace App\Observers;

use App\Enums\StableStatus;
use App\Models\Stable;

class StableObserver
{
    /**
     * Handle the Stable "saved" event.
     *
     * @param  App\Models\Stable $stable
     * @return void
     */
    public function saving(Stable $stable)
    {
        $stable->status = match (true) {
            $stable->isCurrentlyActivated() => StableStatus::active(),
            $stable->hasFutureActivation() => StableStatus::future_activation(),
            $stable->isDeactivated() => StableStatus::inactive(),
            $stable->isRetired() => StableStatus::retired(),
            default => StableStatus::unactivated()
        };
    }
}
