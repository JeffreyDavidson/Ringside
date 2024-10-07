<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\ManagerTagTeam;
use App\Models\ManagerWrestler;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait Manageables
{
    /**
     * Get all the wrestlers that have been managed by model.
     *
     * @return BelongsToMany<Wrestler>
     */
    public function wrestlers(): BelongsToMany
    {
        return $this->belongsToMany(Wrestler::class)
            ->withPivot(['hired_at', 'left_at'])
            ->using(ManagerWrestler::class);
    }

    /**
     * Get the current wrestlers that is managed by model.
     *
     * @return BelongsToMany<Wrestler>
     */
    public function currentWrestlers(): BelongsToMany
    {
        return $this->wrestlers()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous wrestlers that have been managed by model.
     *
     * @return BelongsToMany<Wrestler>
     */
    public function previousWrestlers(): BelongsToMany
    {
        return $this->wrestlers()
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get all the tag teams that have been managed by model.
     *
     * @return BelongsToMany<TagTeam>
     */
    public function tagTeams(): BelongsToMany
    {
        return $this->morphedByMany(TagTeam::class, 'manageable')
            ->withPivot(['hired_at', 'left_at'])
            ->using(ManagerTagTeam::class);
    }

    /**
     * Get all previous tag teams that have been managed by model.
     *
     * @return BelongsToMany<TagTeam>
     */
    public function currentTagTeams(): BelongsToMany
    {
        return $this->tagTeams()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous tag teams that have been managed by model.
     *
     * @return BelongsToMany<TagTeam>
     */
    public function previousTagTeams(): BelongsToMany
    {
        return $this->tagTeams()
            ->wherePivotNotNull('left_at');
    }
}
