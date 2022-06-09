<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\TagTeamStatus;
use App\Models\TagTeam;

class TagTeamObserver
{
    /**
     * Handle the TagTeam "saved" event.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return void
     */
    public function saving(TagTeam $tagTeam)
    {
        $tagTeam->status = match (true) {
            $tagTeam->isCurrentlyEmployed() => match (true) {
                $tagTeam->isSuspended() => TagTeamStatus::SUSPENDED,
                $tagTeam->isUnbookable() => TagTeamStatus::UNBOOKABLE,
                default => TagTeamStatus::BOOKABLE,
            },
            $tagTeam->hasFutureEmployment() => TagTeamStatus::FUTURE_EMPLOYMENT,
            $tagTeam->isReleased() => TagTeamStatus::RELEASED,
            $tagTeam->isRetired() => TagTeamStatus::RETIRED,
            default => TagTeamStatus::UNEMPLOYED
        };
    }
}
