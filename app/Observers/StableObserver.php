<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\StableStatus;
use App\Models\Stable;

class StableObserver
{
    /**
     * Handle the Stable "saved" event.
     */
    public function saving(Stable $stable): void
    {
        $stable->status = match (true) {
            $stable->isCurrentlyActivated() => StableStatus::ACTIVE,
            $stable->hasFutureActivation() => StableStatus::FUTURE_ACTIVATION,
            $stable->isDeactivated() => StableStatus::INACTIVE,
            $stable->isRetired() => StableStatus::RETIRED,
            default => StableStatus::UNACTIVATED
        };
    }
}
