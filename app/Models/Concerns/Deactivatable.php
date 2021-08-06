<?php

namespace App\Models\Concerns;

use App\Models\Activation;

trait Deactivatable
{
    /**
     * Scope a query to only include unactivated models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeactivated($query)
    {
        return $query->whereDoesntHave('currentActivation')
                    ->orWhereDoesntHave('previousActivations');
    }

    /**
     * Scope a query to include current deactivation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithLastDeactivatedAtDate($query)
    {
        return $query->addSelect(['last_deactivated_at' => Activation::select('ended_at')
            ->whereColumn('activatable_id', $query->qualifyColumn('id'))
            ->where('activatable_type', $this->getMorphClass())
            ->orderBy('ended_at', 'desc')
            ->limit(1),
        ])->withCasts(['last_deactivated_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models current deactivation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByLastDeactivatedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(last_deactivated_at) $direction");
    }

    /**
     * Check to see if the model is deactivated.
     *
     * @return bool
     */
    public function isDeactivated()
    {
        return $this->previousActivation()->exists() &&
                $this->currentActivation()->doesntExist() &&
                $this->currentRetirement()->doesntExist();
    }
}
