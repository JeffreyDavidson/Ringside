<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Manageables
{
    /**
     * Get all the wrestlers that have been managed by model.
     *
     * @return MorphToMany<Wrestler>
     */
    public function wrestlers(): MorphToMany
    {
        return $this->morphedByMany(Wrestler::class, 'manageable')
            ->withPivot(['hired_at', 'left_at']);
    }

    /**
     * Get the current wrestlers that is managed by model.
     *
     * @return MorphToMany<Wrestler>
     */
    public function currentWrestlers(): MorphToMany
    {
        return $this->morphedByMany(Wrestler::class, 'manageable')
            ->withPivot(['hired_at', 'left_at'])
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous wrestlers that have been managed by model.
     *
     * @return MorphToMany<Wrestler>
     */
    public function previousWrestlers(): MorphToMany
    {
        return $this->morphedByMany(Wrestler::class, 'manageable')
            ->withPivot(['hired_at', 'left_at'])
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get all the tag teams that have been managed by model.
     *
     * @return MorphToMany<TagTeam>
     */
    public function tagTeams(): MorphToMany
    {
        return $this->morphedByMany(TagTeam::class, 'manageable')
            ->withPivot(['hired_at', 'left_at']);
    }

    /**
     * Get all previous tag teams that have been managed by model.
     *
     * @return MorphToMany<TagTeam>
     */
    public function currentTagTeams(): MorphToMany
    {
        return $this->morphedByMany(TagTeam::class, 'manageable')
            ->withPivot(['hired_at', 'left_at'])
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous tag teams that have been managed by model.
     *
     * @return MorphToMany<TagTeam>
     */
    public function previousTagTeams(): MorphToMany
    {
        return $this->morphedByMany(TagTeam::class, 'manageable')
            ->withPivot(['hired_at', 'left_at'])
            ->wherePivotNotNull('left_at');
    }
}
