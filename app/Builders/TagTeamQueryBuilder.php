<?php

declare(strict_types=1);

namespace App\Builders;

/**
 * @template TModelClass of \App\Models\TagTeam
 *
 * @extends \App\Builders\RosterMemberQueryBuilder<\App\Models\TagTeam>
 */
class TagTeamQueryBuilder extends RosterMemberQueryBuilder
{
    /**
     * Scope a query to only include bookable tag teams.
     */
    public function bookable(): TagTeamQueryBuilder
    {
        return $this->where('status', 'bookable');
    }

    /**
     * Scope a query to only include bookable tag teams.
     */
    public function unbookable(): TagTeamQueryBuilder
    {
        return $this->where('status', 'unbookable');
    }
}
