<?php

namespace App\Models\Concerns;

use App\Enums\TitleStatus;
use Illuminate\Database\Eloquent\Builder;

trait Competable
{
    /**
     * Scope a query to only include competable models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompetable(Builder $query)
    {
        return $query->where('status', TitleStatus::active());
    }

    /**
     * Check to see if the model can be competed for.
     *
     * @return bool
     */
    public function isCompetable()
    {
        if ($this->isNotActivation() || $this->isDeactivated() || $this->isRetired() || $this->hasFutureActivation()) {
            return false;
        }

        return true;
    }
}
