<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\RefereeStatus;
use App\Models\Referee;

class RefereeObserver
{
    /**
     * Handle the Referee "saved" event.
     *
     * @param  \App\Models\Referee  $referee
     * @return void
     */
    public function saving(Referee $referee)
    {
        $referee->status = match (true) {
            $referee->isCurrentlyEmployed() => match (true) {
                $referee->isInjured() => RefereeStatus::INJURED,
                $referee->isSuspended() => RefereeStatus::SUSPENDED,
                default => RefereeStatus::BOOKABLE,
            },
            $referee->hasFutureEmployment() => RefereeStatus::FUTURE_EMPLOYMENT,
            $referee->isReleased() => RefereeStatus::RELEASED,
            $referee->isRetired() => RefereeStatus::RETIRED,
            default => RefereeStatus::UNEMPLOYED
        };
    }
}
