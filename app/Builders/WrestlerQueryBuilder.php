<?php

declare(strict_types=1);

namespace App\Builders;

/**
 * @template TModelClass of \App\Models\Wrestler
 *
 * @extends SingleRosterMemberQueryBuilder<\App\Models\Wrestler>
 */
class WrestlerQueryBuilder extends SingleRosterMemberQueryBuilder
{
    /**
     * Scope a query to only include bookable wrestlers.
     */
    public function bookable(): WrestlerQueryBuilder
    {
        return $this->whereHas('currentEmployment')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentInjury');
    }
}
