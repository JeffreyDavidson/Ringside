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
                $referee->isInjured() => RefereeStatus::Injured->value,
                $referee->isSuspended() => RefereeStatus::Suspended->value,
                default => RefereeStatus::Bookable->value,
            },
            $referee->hasFutureEmployment() => RefereeStatus::FutureEmployment->value,
            $referee->isReleased() => RefereeStatus::Released->value,
            $referee->isRetired() => RefereeStatus::Retired->value,
            default => RefereeStatus::Unemployed->value
        };
    }
}
