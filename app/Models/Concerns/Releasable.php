<?php

namespace App\Models\Concerns;

use App\Models\Employment;
use Illuminate\Database\Eloquent\Builder;

trait Releasable
{
    /**
     * Scope a query to include released models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReleased(Builder $query)
    {
        return $query->whereHas('previousEmployment')
                    ->whereDoesntHave('currentEmployment')
                    ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to include released date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithReleasedAtDate(Builder $query)
    {
        return $query->addSelect(['released_at' => Employment::select('ended_at')
            ->whereColumn('employable_id', $this->getTable().'.id')
            ->where('employable_type', $this->getMorphClass())
            ->latest('ended_at')
            ->limit(1),
        ])->withCasts(['released_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current released date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentReleasedAtDate(Builder $query, string $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_released_at) $direction");
    }

    /**
     * Check to see if the model has been released.
     *
     * @return bool
     */
    public function isReleased()
    {
        return $this->previousEmployment()->exists() &&
                $this->futureEmployment()->doesntExist() &&
                $this->currentEmployment()->doesntExist() &&
                $this->currentRetirement()->doesntExist();
    }

    /**
     * Determine if the model can be released.
     *
     * @return bool
     */
    public function canBeReleased()
    {
        if ($this->isNotInEmployment()) {
            return false;
        }

        return true;
    }
}
