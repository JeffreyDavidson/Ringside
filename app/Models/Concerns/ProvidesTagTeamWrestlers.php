<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TagTeams\TagTeamWrestler;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @template TModel of Model The tag team model using this trait
 */
trait ProvidesTagTeamWrestlers
{
    /**
     * Get all wrestlers that have partnered with the tag team.
     *
     * This method returns a BelongsToMany relationship that includes all wrestler
     * records associated with the tag team, regardless of their status (current or former).
     *
     * @return BelongsToMany<Wrestler, static, TagTeamWrestler>
     *                                                          A relationship instance for accessing all wrestlers
     *
     * @example
     * ```php
     * $tagTeam = TagTeam::find(1);
     * $allWrestlers = $tagTeam->wrestlers;
     * $wrestlerCount = $tagTeam->wrestlers()->count();
     * ```
     */
    public function wrestlers(): BelongsToMany
    {
        /** @var BelongsToMany<Wrestler, static, TagTeamWrestler> $relation */
        $relation = $this->belongsToMany(Wrestler::class, 'tag_teams_wrestlers')
            ->using(TagTeamWrestler::class)
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();

        return $relation;
    }

    /**
     * Get wrestlers currently part of the tag team.
     *
     * Returns a BelongsToMany relationship for wrestlers who are currently
     * active members of the tag team (no 'left_at' date).
     *
     * @return BelongsToMany<Wrestler, static, TagTeamWrestler>
     *                                                          A relationship instance for accessing current wrestlers
     *
     * @example
     * ```php
     * $tagTeam = TagTeam::find(1);
     * $currentWrestlers = $tagTeam->currentWrestlers;
     *
     * if ($tagTeam->currentWrestlers()->exists()) {
     *     echo "Tag team has active wrestlers";
     * }
     * ```
     */
    public function currentWrestlers(): BelongsToMany
    {
        /** @var BelongsToMany<Wrestler, static, TagTeamWrestler> $relation */
        $relation = $this->belongsToMany(Wrestler::class, 'tag_teams_wrestlers')
            ->using(TagTeamWrestler::class)
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps()
            ->wherePivotNull('left_at');

        return $relation;
    }

    /**
     * Get wrestlers who were previously part of the tag team.
     *
     * Returns a BelongsToMany relationship for wrestlers who were once
     * members of the tag team but have since left (have a 'left_at' date).
     *
     * @return BelongsToMany<Wrestler, static, TagTeamWrestler>
     *                                                          A relationship instance for accessing previous wrestlers
     *
     * @example
     * ```php
     * $tagTeam = TagTeam::find(1);
     * $formerWrestlers = $tagTeam->previousWrestlers;
     * $wrestlerHistory = $tagTeam->previousWrestlers()->orderBy('pivot_left_at', 'desc')->get();
     * ```
     */
    public function previousWrestlers(): BelongsToMany
    {
        /** @var BelongsToMany<Wrestler, static, TagTeamWrestler> $relation */
        $relation = $this->belongsToMany(Wrestler::class, 'tag_teams_wrestlers')
            ->using(TagTeamWrestler::class)
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps()
            ->wherePivotNotNull('left_at');

        return $relation;
    }

    /**
     * Calculate the combined weight of the current wrestler members.
     *
     * This attribute calculates the total weight of all wrestlers currently
     * in the tag team by summing their individual weights.
     *
     * @return Attribute<int, never> The combined weight attribute
     *
     * @example
     * ```php
     * $tagTeam = TagTeam::find(1);
     * echo $tagTeam->combined_weight; // Returns total weight of current wrestlers
     * ```
     */
    public function combinedWeight(): Attribute
    {
        return Attribute::get(fn () => $this->currentWrestlers->sum('weight'));
    }
}
