<?php

namespace App\Builders;

use App\Models\Employment;
use App\Models\Retirement;
use App\Models\Suspension;
use Illuminate\Database\Eloquent\Builder;

class RosterMemberQueryBuilder extends Builder
{
    /**
     * Scope a query to include suspended models.
     *
     * @return $this
     */
    public function suspended()
    {
        return $this->whereHas('currentSuspension');
    }

    /**
     * Scope a query to include current suspension date.
     *
     * @return $this
     */
    public function withCurrentSuspendedAtDate()
    {
        return $this->addSelect(['current_suspended_at' => Suspension::select('started_at')
            ->whereColumn('suspendable_id', $this->qualifyColumn('id'))
            ->where('suspendable_type', $this->getModel())
            ->latest('started_at')
            ->limit(1),
        ])->withCasts(['current_suspended_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current suspension date.
     *
     * @param  string  $direction
     * @return $this
     */
    public function orderByCurrentSuspendedAtDate(string $direction = 'asc')
    {
        return $this->orderByRaw("DATE(current_suspended_at) $direction");
    }

    /**
     * Scope a query to only include retired models.
     *
     * @return $this
     */
    public function retired()
    {
        return $this->whereHas('currentRetirement');
    }

    /**
     * Scope a query to include current retirement date.
     *
     * @return $this
     */
    public function withCurrentRetiredAtDate()
    {
        return $this->addSelect(['current_retired_at' => Retirement::select('started_at')
            ->whereColumn('retiree_id', $this->getModel()->getTable().'.id')
            ->where('retiree_type', $this->getModel())
            ->latest('started_at')
            ->limit(1),
        ])->withCasts(['current_retired_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current retirement date.
     *
     * @param  string  $direction
     * @return $this
     */
    public function orderByCurrentRetiredAtDate(string $direction = 'asc')
    {
        return $this->orderByRaw("DATE(current_retired_at) $direction");
    }

    /**
     * Scope a query to include released models.
     *
     * @return $this
     */
    public function released()
    {
        return $this->whereHas('previousEmployment')
                    ->whereDoesntHave('currentEmployment')
                    ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to include released date.
     *
     * @return $this
     */
    public function withReleasedAtDate()
    {
        return $this->addSelect(['released_at' => Employment::select('ended_at')
            ->whereColumn('employable_id', $this->getModel()->getTable().'.id')
            ->where('employable_type', $this->getModel())
            ->latest('ended_at')
            ->limit(1),
        ])->withCasts(['released_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current released date.
     *
     * @param  string  $direction
     * @return $this
     */
    public function orderByCurrentReleasedAtDate(string $direction = 'asc')
    {
        return $this->orderByRaw("DATE(current_released_at) $direction");
    }

    /**
     * Scope a query to include employed models.
     *
     * @return $this
     */
    public function employed()
    {
        return $this->whereHas('currentEmployment');
    }

    /**
     * Scope a query to only include future employed models.
     *
     * @return $this
     */
    public function futureEmployed()
    {
        return $this->whereHas('futureEmployment');
    }

    /**
     * Scope a query to include unemployed models.
     *
     * @return $this
     */
    public function unemployed()
    {
        return $this->whereDoesntHave('currentEmployment')
                    ->orWhereDoesntHave('previousEmployments');
    }

    /**
     * Scope a query to include first employment date.
     *
     * @return $this
     */
    public function withFirstEmployedAtDate()
    {
        return $this->addSelect(['first_employed_at' => Employment::select('started_at')
            ->whereColumn('employable_id', $this->qualifyColumn('id'))
            ->where('employable_type', $this->getModel())
            ->oldest('started_at')
            ->limit(1),
        ])->withCasts(['first_employed_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's first employment date.
     *
     * @param  string $direction
     * @return $this
     */
    public function orderByFirstEmployedAtDate(string $direction = 'asc')
    {
        return $this->orderByRaw("DATE(first_employed_at) $direction");
    }
}
