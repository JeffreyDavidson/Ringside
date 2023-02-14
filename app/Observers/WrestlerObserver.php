<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\WrestlerStatus;
use App\Models\Wrestler;

class WrestlerObserver
{
    /**
     * Handle the Wrestler "saved" event.
     */
    public function saving(Wrestler $wrestler): void
    {
        $wrestler->status = match (true) {
            $wrestler->isCurrentlyEmployed() => match (true) {
                $wrestler->isInjured() => WrestlerStatus::INJURED,
                $wrestler->isSuspended() => WrestlerStatus::SUSPENDED,
                default => WrestlerStatus::BOOKABLE,
            },
            $wrestler->hasFutureEmployment() => WrestlerStatus::FUTURE_EMPLOYMENT,
            $wrestler->isReleased() => WrestlerStatus::RELEASED,
            $wrestler->isRetired() => WrestlerStatus::RETIRED,
            default => WrestlerStatus::UNEMPLOYED
        };
    }
}
