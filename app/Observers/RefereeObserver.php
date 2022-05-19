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
     * @param  \App\Models\Referee $referee
     *
     * @return void
     */
    public function saving(Referee $referee)
    {
        $referee->status = match (true) {
            $referee->isCurrentlyEmployed() => match (true) {
                $referee->isInjured() => RefereeStatus::injured(),
                $referee->isSuspended() => RefereeStatus::suspended(),
                default => RefereeStatus::bookable(),
            },
            $referee->hasFutureEmployment() => RefereeStatus::future_employment(),
            $referee->isReleased() => RefereeStatus::released(),
            $referee->isRetired() => RefereeStatus::retired(),
            default => RefereeStatus::unemployed()
        };
    }
}
