<?php

declare(strict_types=1);

namespace App\Builders;

class TagTeamQueryBuilder extends RosterMemberQueryBuilder
{
    /**
     * Scope a query to only include bookable tag teams.
     *
     * @return \App\Builders\TagTeamQueryBuilder
     */
    public function bookable()
    {
        return $this->where('status', 'bookable');
    }
}
