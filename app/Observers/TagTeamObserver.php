<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\TagTeamStatus;
use App\Models\TagTeam;

class TagTeamObserver
{
    /**
     * Handle the TagTeam "saved" event.
     */
    public function saving(TagTeam $tagTeam): void
    {
        $tagTeam->status = match (true) {
            $tagTeam->isCurrentlyEmployed() => match (true) {
                $tagTeam->isSuspended() => TagTeamStatus::Suspended,
                $tagTeam->isUnbookable() => TagTeamStatus::Unbookable,
                default => TagTeamStatus::Bookable,
            },
            $tagTeam->hasFutureEmployment() => TagTeamStatus::FutureEmployment,
            $tagTeam->isReleased() => TagTeamStatus::Released,
            $tagTeam->isRetired() => TagTeamStatus::Retired,
            default => TagTeamStatus::Unemployed
        };
    }
}
