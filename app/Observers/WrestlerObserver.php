<?php

namespace App\Observers;

use App\Enums\WrestlerStatus;
use App\Models\Wrestler;

class WrestlerObserver
{
    /**
     * Handle the Wrestler "saved" event.
     *
     * @param  App\Models\Wrestler $wrestler
     * @return void
     */
    public function saving(Wrestler $wrestler)
    {
        $wrestler->status = match (true) {
            $wrestler->isCurrentlyEmployed() => match (true) {
                $wrestler->isInjured() => WrestlerStatus::injured(),
                $wrestler->isSuspended() => WrestlerStatus::suspended(),
                $wrestler->isBookable() => WrestlerStatus::bookable(),
            },
            $wrestler->hasFutureEmployment() => WrestlerStatus::future_employment(),
            $wrestler->isReleased() => WrestlerStatus::released(),
            $wrestler->isRetired() => WrestlerStatus::retired(),
            default => WrestlerStatus::unemployed()
        };
    }
}
