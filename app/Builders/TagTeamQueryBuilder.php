<?php

namespace App\Builders;

class TagTeamQueryBuilder extends RosterMemberQueryBuilder
{
    /**
     * Scope a query to only include bookable tag teams.
     *
     * @return $this
     */
    public function bookable()
    {
        return $this->where('status', 'bookable');
    }
}
