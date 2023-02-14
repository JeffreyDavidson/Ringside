<?php

declare(strict_types=1);

namespace App\Builders;

use App\Builders\SingleRosterMemberQueryBuilder;
use App\Models\Injury;

/**
 * @template TModelClass of \App\Models\SingleRosterMember
 *
 * @extends RosterMemberQueryBuilder<\App\Models\SingleRosterMember>
 */
class SingleRosterMemberQueryBuilder extends RosterMemberQueryBuilder
{
    /**
     * Scope a query to only include injured models.
     */
    public function injured(): SingleRosterMemberQueryBuilder
    {
        return $this->whereHas('currentInjury');
    }

    /**
     * Scope a query to include current injured date.
     */
    public function withCurrentInjuredAtDate(): SingleRosterMemberQueryBuilder
    {
        return $this->addSelect([
            'current_injured_at' => Injury::select('started_at')
                ->whereColumn('injurable_id', $this->qualifyColumn('id'))
                ->where('injurable_type', $this->getModel())
                ->latest('started_at')
                ->limit(1),
        ])->withCasts(['current_injured_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current injured date.
     */
    public function orderByCurrentInjuredAtDate(string $direction = 'asc'): SingleRosterMemberQueryBuilder
    {
        return $this->orderByRaw("DATE(current_injured_at) {$direction}");
    }

    /**
     * Scope a query to only include bookable models.
     */
    public function bookable(): SingleRosterMemberQueryBuilder
    {
        return $this->whereHas('currentEmployment')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentInjury');
    }
}
