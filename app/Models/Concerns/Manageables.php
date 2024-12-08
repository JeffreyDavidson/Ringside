<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait Manageables
{
    /**
     * Get all the wrestlers that have been managed by model.
     *
     * @return BelongsToMany<Wrestler, $this>
     */
    public function wrestlers(): BelongsToMany
    {
        return $this->belongsToMany(Wrestler::class, 'wrestlers_managers')
            ->withPivot(['hired_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * Get the current wrestlers that is managed by model.
     *
     * @return BelongsToMany<Wrestler, $this>
     */
    public function currentWrestlers(): BelongsToMany
    {
        return $this->wrestlers()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous wrestlers that have been managed by model.
     *
     * @return BelongsToMany<Wrestler, $this>
     */
    public function previousWrestlers(): BelongsToMany
    {
        return $this->wrestlers()
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get all the tag teams that have been managed by model.
     *
     * @return BelongsToMany<TagTeam, $this>
     */
    public function tagTeams(): BelongsToMany
    {
        return $this->belongsToMany(TagTeam::class, 'tag_teams_managers')
            ->withPivot(['hired_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * Get all previous tag teams that have been managed by model.
     *
     * @return BelongsToMany<TagTeam, $this>
     */
    public function currentTagTeams(): BelongsToMany
    {
        return $this->tagTeams()
            ->wherePivotNull('left_at');
    }

    /**
     * Get all previous tag teams that have been managed by model.
     *
     * @return BelongsToMany<TagTeam, $this>
     */
    public function previousTagTeams(): BelongsToMany
    {
        return $this->tagTeams()
            ->wherePivotNotNull('left_at');
    }
}
