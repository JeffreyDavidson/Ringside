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
        if ($tagTeam->isCurrentlyEmployed()) {
            if ($tagTeam->isSuspended()) {
                $tagTeam->status = TagTeamStatus::SUSPENDED;
            } elseif ($tagTeam->isBookable()) {
                $tagTeam->status = TagTeamStatus::BOOKABLE;
            } elseif ($tagTeam->isUnbookable()) {
                $tagTeam->status = TagTeamStatus::UNBOOKABLE;
            }
        } elseif ($tagTeam->isReleased()) {
            $tagTeam->status = TagTeamStatus::RELEASED;
        } elseif ($tagTeam->hasFutureEmployment()) {
            $tagTeam->status = TagTeamStatus::FUTURE_EMPLOYMENT;
        } elseif ($tagTeam->isRetired()) {
            $tagTeam->status = TagTeamStatus::RETIRED;
        } else {
            $tagTeam->status = TagTeamStatus::UNEMPLOYED;
        }
    }
}
