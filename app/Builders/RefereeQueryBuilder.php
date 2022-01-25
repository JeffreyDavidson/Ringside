<?php

namespace App\Builders;

class RefereeQueryBuilder extends SingleRosterMemberQueryBuilder
{
    /**
     * Scope a query to only include bookable models.
     *
     * @return \App\Builders\RefereeQueryBuilder
     */
    public function bookable()
    {
        return $this->whereHas('currentEmployment')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentInjury');
    }
}
