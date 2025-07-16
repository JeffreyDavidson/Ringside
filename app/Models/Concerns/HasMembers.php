<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Provides relationship methods to access wrestlers and tag teams
 * that are or have been part of a stable.
 *
 * This trait uses separate pivot tables (stables_wrestlers, stables_tag_teams)
 * to manage stable membership with joined_at/left_at timestamps.
 *
 * It includes helpers to retrieve:
 * - All members of each type (wrestlers, tag teams)
 * - Currently active members (those without a `left_at` timestamp)
 * - Previously associated members (those with a `left_at` timestamp)
 *
 * Note: Managers are NOT direct stable members. They are associated
 * with wrestlers/tag teams through hired_at/fired_at relationships.
 *
 * @example
 * ```php
 * class Stable extends Model
 * {
 *     use HasMembers;
 * }
 *
 * $stable = Stable::find(1);
 * $allWrestlers = $stable->wrestlers;
 * $currentWrestlers = $stable->currentWrestlers;
 * $formerTagTeams = $stable->previousTagTeams;
 * ```
 */
trait HasMembers
{
    // ==================== WRESTLER RELATIONSHIPS ====================

    /**
     * Get all wrestlers that have ever been part of the stable.
     *
     * @return BelongsToMany<Wrestler, static>
     *                                         A relationship instance for accessing all wrestlers
     *
     * @example
     * ```php
     * $stable = Stable::find(1);
     * $allWrestlers = $stable->wrestlers;
     * $wrestlerCount = $stable->wrestlers()->count();
     * ```
     */
    public function wrestlers(): BelongsToMany
    {
        /** @var BelongsToMany<Wrestler, static> $relation */
        $relation = $this->belongsToMany(Wrestler::class, 'stables_wrestlers')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();

        return $relation;
    }

    /**
     * Get wrestlers currently part of the stable.
     *
     * Returns wrestlers who are active members (no 'left_at' timestamp).
     *
     * @return BelongsToMany<Wrestler, static>
     *                                         A relationship instance for accessing current wrestlers
     *
     * @example
     * ```php
     * $stable = Stable::find(1);
     * $currentWrestlers = $stable->currentWrestlers;
     *
     * if ($stable->currentWrestlers()->exists()) {
     *     echo "Stable has active wrestlers";
     * }
     * ```
     */
    public function currentWrestlers(): BelongsToMany
    {
        /** @var BelongsToMany<Wrestler, static> $relation */
        $relation = $this->belongsToMany(Wrestler::class, 'stables_wrestlers')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps()
            ->wherePivotNull('left_at');

        return $relation;
    }

    /**
     * Get wrestlers who were previously part of the stable.
     *
     * Returns wrestlers who have left the stable (have a 'left_at' timestamp).
     *
     * @return BelongsToMany<Wrestler, static>
     *                                         A relationship instance for accessing previous wrestlers
     *
     * @example
     * ```php
     * $stable = Stable::find(1);
     * $formerWrestlers = $stable->previousWrestlers;
     * $wrestlerHistory = $stable->previousWrestlers()->orderBy('pivot_left_at', 'desc')->get();
     * ```
     */
    public function previousWrestlers(): BelongsToMany
    {
        /** @var BelongsToMany<Wrestler, static> $relation */
        $relation = $this->belongsToMany(Wrestler::class, 'stables_wrestlers')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps()
            ->wherePivotNotNull('left_at');

        return $relation;
    }

    // ==================== TAG TEAM RELATIONSHIPS ====================

    /**
     * Get all tag teams that have ever been part of the stable.
     *
     * @return BelongsToMany<TagTeam, static>
     *                                        A relationship instance for accessing all tag teams
     *
     * @example
     * ```php
     * $stable = Stable::find(1);
     * $allTagTeams = $stable->tagTeams;
     * $tagTeamCount = $stable->tagTeams()->count();
     * ```
     */
    public function tagTeams(): BelongsToMany
    {
        /** @var BelongsToMany<TagTeam, static> $relation */
        $relation = $this->belongsToMany(TagTeam::class, 'stables_tag_teams')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();

        return $relation;
    }

    /**
     * Get tag teams currently part of the stable.
     *
     * Returns tag teams who are active members (no 'left_at' timestamp).
     *
     * @return BelongsToMany<TagTeam, static>
     *                                        A relationship instance for accessing current tag teams
     *
     * @example
     * ```php
     * $stable = Stable::find(1);
     * $currentTagTeams = $stable->currentTagTeams;
     *
     * if ($stable->currentTagTeams()->exists()) {
     *     echo "Stable has active tag teams";
     * }
     * ```
     */
    public function currentTagTeams(): BelongsToMany
    {
        /** @var BelongsToMany<TagTeam, static> $relation */
        $relation = $this->belongsToMany(TagTeam::class, 'stables_tag_teams')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps()
            ->wherePivotNull('left_at');

        return $relation;
    }

    /**
     * Get tag teams who were previously part of the stable.
     *
     * Returns tag teams who have left the stable (have a 'left_at' timestamp).
     *
     * @return BelongsToMany<TagTeam, static>
     *                                        A relationship instance for accessing previous tag teams
     *
     * @example
     * ```php
     * $stable = Stable::find(1);
     * $formerTagTeams = $stable->previousTagTeams;
     * $tagTeamHistory = $stable->previousTagTeams()->orderBy('pivot_left_at', 'desc')->get();
     * ```
     */
    public function previousTagTeams(): BelongsToMany
    {
        /** @var BelongsToMany<TagTeam, static> $relation */
        $relation = $this->belongsToMany(TagTeam::class, 'stables_tag_teams')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps()
            ->wherePivotNotNull('left_at');

        return $relation;
    }
}
