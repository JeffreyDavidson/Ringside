<?php

namespace App\Observers;

use App\Enums\TagTeamStatus;
use App\Models\TagTeam;

class TagTeamObserver
{
    /**
     * Handle the TagTeam "saved" event.
     *
     * @param  \App\Models\TagTeam $tagTeam
     *
     * @return void
     */
    public function saving(TagTeam $tagTeam)
    {
        $tagTeam->status = match (true) {
            $tagTeam->isCurrentlyEmployed() => match (true) {
                $tagTeam->isSuspended() => TagTeamStatus::suspended(),
                $tagTeam->isUnbookable() => TagTeamStatus::unbookable(),
                default => TagTeamStatus::bookable(),
            },
            $tagTeam->hasFutureEmployment() => TagTeamStatus::future_employment(),
            $tagTeam->isReleased() => TagTeamStatus::released(),
            $tagTeam->isRetired() => TagTeamStatus::retired(),
            default => TagTeamStatus::unemployed()
        };
    }
}
