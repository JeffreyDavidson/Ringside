<?php

namespace App\Builders;

use App\Models\Injury;

/**
 * @template TModelClass of \App\Models\SingleRosterMember
 * @extends RosterMemberQueryBuilder<TModelClass>
 */
class SingleRosterMemberQueryBuilder extends RosterMemberQueryBuilder
{
    /**
     * Scope a query to only include injured models.
     *
     * @return \App\Builders\SingleRosterMemberQueryBuilder
     */
    public function injured()
    {
        return $this->whereHas('currentInjury');
    }

    /**
     * Scope a query to include current injured date.
     *
     * @return \App\Builders\SingleRosterMemberQueryBuilder
     */
    public function withCurrentInjuredAtDate()
    {
        return $this->addSelect(['current_injured_at' => Injury::select('started_at')
            ->whereColumn('injurable_id', $this->qualifyColumn('id'))
            ->where('injurable_type', $this->getModel())
            ->latest('started_at')
            ->limit(1),
        ])->withCasts(['current_injured_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current injured date.
     *
     * @param  string  $direction
     * @return \App\Builders\SingleRosterMemberQueryBuilder
     */
    public function orderByCurrentInjuredAtDate(string $direction = 'asc')
    {
        return $this->orderByRaw("DATE(current_injured_at) {$direction}");
    }

    /**
     * Scope a query to only include bookable models.
     *
     * @return \App\Builders\SingleRosterMemberQueryBuilder
     */
    public function bookable()
    {
        return $this->whereHas('currentEmployment')
                    ->whereDoesntHave('currentSuspension')
                    ->whereDoesntHave('currentInjury');
    }
}
