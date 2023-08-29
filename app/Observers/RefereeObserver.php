<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\RefereeStatus;
use App\Models\Referee;

class RefereeObserver
{
    /**
     * Handle the Referee "saved" event.
     */
    public function saving(Referee $referee): void
    {
        $referee->status = match (true) {
            $referee->isCurrentlyEmployed() => match (true) {
                $referee->isInjured() => RefereeStatus::Injured,
                $referee->isSuspended() => RefereeStatus::Suspended,
                default => RefereeStatus::Bookable,
            },
            $referee->hasFutureEmployment() => RefereeStatus::FutureEmployment,
            $referee->isReleased() => RefereeStatus::Released,
            $referee->isRetired() => RefereeStatus::Retired,
            default => RefereeStatus::Unemployed
        };
    }
}
