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
            $stable->isCurrentlyActivated() => StableStatus::Active,
            $stable->hasFutureActivation() => StableStatus::FutureActivation,
            $stable->isDeactivated() => StableStatus::Inactive,
            $stable->isRetired() => StableStatus::Retired,
            default => StableStatus::Unactivated
        };
    }
}
