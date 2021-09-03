<?php

namespace App\Models\Concerns;

use App\Models\TagTeam;
use App\Models\Wrestler;

trait Manageables
{
    /**
     * Get all of the wrestlers that have been managed by model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function wrestlers()
    {
        return $this->morphedByMany(Wrestler::class, 'manageable')->withPivot(['hired_at', 'left_at']);
    }

    /**
     * Get the current wrestlers that is managed by model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function currentWrestlers()
    {
        return $this->morphedByMany(Wrestler::class, 'manageable')->wherePivotNull('left_at');
    }

    /**
     * Get all previous wrestlers that have been managed by model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function previousWrestlers()
    {
        return $this->morphedByMany(Wrestler::class, 'manageable')->wherePivotNotNull('left_at');
    }

    /**
     * Get all of the tag teams that have been managed by model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function tagTeams()
    {
        return $this->morphedByMany(TagTeam::class, 'manageable')->withPivot(['hired_at', 'left_at']);
    }

    /**
     * Get all previous tag teams that have been managed by model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function currentTagTeams()
    {
        return $this->morphedByMany(TagTeam::class, 'manageable')->withPivot(['hired_at', 'left_at'])->wherePivotNull('left_at');
    }

    /**
     * Get all previous tag teams that have been managed by model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function previousTagTeams()
    {
        return $this->morphedByMany(TagTeam::class, 'manageable')->withPivot(['hired_at', 'left_at'])->wherePivotNotNull('left_at');
    }
}
