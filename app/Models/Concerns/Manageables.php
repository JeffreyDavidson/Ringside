<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Manageables
{
    /**
     * Get all of the wrestlers that have been managed by model.
     */
    public function wrestlers(): MorphToMany
    {
        return $this->morphedByMany(Wrestler::class, 'manageable')
            ->withPivot(['hired_at', 'left_at']);
    }

    /**
     * Get the current wrestlers that is managed by model.
     */
    public function currentWrestlers(): MorphToMany
    {
        return $this->morphedByMany(Wrestler::class, 'manageable')
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous wrestlers that have been managed by model.
     */
    public function previousWrestlers(): MorphToMany
    {
        return $this->morphedByMany(Wrestler::class, 'manageable')
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get all of the tag teams that have been managed by model.
     */
    public function tagTeams(): MorphToMany
    {
        return $this->morphedByMany(TagTeam::class, 'manageable')
            ->withPivot(['hired_at', 'left_at']);
    }

    /**
     * Get all previous tag teams that have been managed by model.
     */
    public function currentTagTeams(): MorphToMany
    {
        return $this->morphedByMany(TagTeam::class, 'manageable')
            ->withPivot(['hired_at', 'left_at'])
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous tag teams that have been managed by model.
     */
    public function previousTagTeams(): MorphToMany
    {
        return $this->morphedByMany(TagTeam::class, 'manageable')
            ->withPivot(['hired_at', 'left_at'])
            ->wherePivotNotNull('left_at');
    }
}
