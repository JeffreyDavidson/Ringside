<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TagTeams\TagTeamWrestler;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Simple trait for tag teams that have wrestler relationships.
 *
 * This trait provides basic wrestler relationship methods as a lightweight
 * alternative to the more comprehensive ProvidesTagTeamWrestlers trait.
 * It includes methods for accessing current, previous, and all wrestlers,
 * plus computed attributes for team statistics.
 *
 * @template TPivotModel of Pivot The pivot model for wrestler relationships
 *
 * @example
 * ```php
 * class TagTeam extends Model
 * {
 *     use HasWrestlers;
 * }
 *
 * $tagTeam = TagTeam::find(1);
 * $currentWrestlers = $tagTeam->currentWrestlers;
 * $combinedWeight = $tagTeam->combinedWeight;
 * ```
 */
trait HasWrestlers
{
    /**
     * Get the wrestlers that have been tag team partners of the tag team.
     *
     * Returns all wrestler relationships regardless of their current status
     * (active or completed). Includes pivot data for join/leave tracking.
     *
     * @return BelongsToMany<Wrestler, static, TPivotModel>
     */
    public function wrestlers(): BelongsToMany
    {
        return $this->belongsToMany(Wrestler::class, 'tag_teams_wrestlers')
            ->withPivot('joined_at', 'left_at')
            ->using(TagTeamWrestler::class)
            ->withTimestamps();
    }

    /**
     * Get current wrestlers of the tag team.
     *
     * Returns wrestlers who are currently active members of the tag team
     * (where 'left_at' is null).
     *
     * @return BelongsToMany<Wrestler, static, TPivotModel>
     */
    public function currentWrestlers(): BelongsToMany
    {
        return $this->wrestlers()
            ->wherePivotNull('left_at');
    }

    /**
     * Get previous tag team partners of the tag team.
     *
     * Returns wrestlers who were once members but have since left
     * (where 'left_at' is not null).
     *
     * @return BelongsToMany<Wrestler, static, TPivotModel>
     */
    public function previousWrestlers(): BelongsToMany
    {
        return $this->wrestlers()
            ->wherePivotNotNull('left_at');
    }

    /**
     * Get the combined weight of both tag team partners in a tag team.
     *
     * Calculates the total weight of all current wrestlers in the tag team.
     * Useful for match-making and weight class determinations.
     *
     * @return Attribute<int, never>
     */
    public function combinedWeight(): Attribute
    {
        return new Attribute(
            get: fn () => $this->currentWrestlers->sum('weight')
        );
    }
}
