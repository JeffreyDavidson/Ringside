<?php

namespace App\Observers;

use App\Enums\TagTeamStatus;
use App\Models\TagTeam;

class TagTeamObserver
{
    /**
     * Handle the Tag Team "saving" event.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return void
     */
    public function saving(TagTeam $tagTeam)
    {
        if ($tagTeam->isRetired()) {
            $tagTeam->status = TagTeamStatus::RETIRED;
        } elseif ($tagTeam->isSuspended()) {
            $tagTeam->status = TagTeamStatus::SUSPENDED;
        } elseif ($tagTeam->isBookable()) {
            $tagTeam->status = TagTeamStatus::BOOKABLE;
        } elseif ($tagTeam->hasFutureEmployment()) {
            $tagTeam->status = TagTeamStatus::FUTURE_EMPLOYMENT;
        } elseif ($tagTeam->isReleased()) {
            $tagTeam->status = TagTeamStatus::RELEASED;
        } else {
            $tagTeam->status = TagTeamStatus::UNEMPLOYED;
        }
    }
}
