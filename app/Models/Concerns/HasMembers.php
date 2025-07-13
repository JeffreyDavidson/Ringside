<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Stables\StableMember;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Provides relationship methods to access wrestlers and tag teams
 * that are or have been part of a stable.
 *
 * This trait uses a polymorphic approach with the StableMember pivot model
 * to manage stable membership through a single table.
 *
 * It includes helpers to retrieve:
 * - All members of each type (wrestlers, tag teams)
 * - Currently active members (those without a `left_at` timestamp)
 * - Previously associated members (those with a `left_at` timestamp)
 *
 * Note: Managers are not direct stable members. They are associated
 * through the wrestlers/tag teams they manage.
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
    /**
     * Define the many-to-many relationship with a stable member.
     *
     * This is a helper method that sets up the basic many-to-many relationship
     * with the necessary pivot fields and timestamps for tracking membership periods.
     *
     * @template TMemberModel of \Illuminate\Database\Eloquent\Model
     * @template TPivot of \Illuminate\Database\Eloquent\Relations\Pivot
     *
     * @param  class-string<TMemberModel>  $related  The related model class
     * @param  class-string<TPivot>  $pivot  The pivot model class
     * @param  string  $table  The pivot table name
     * @return BelongsToMany<TMemberModel, $this, TPivot>
     *                                                    A relationship instance for accessing members
     */
    protected function members(string $related, string $pivot, string $table): BelongsToMany
    {
        /** @var BelongsToMany<TMemberModel, $this, TPivot> $relation */
        $relation = $this->belongsToMany($related, $table)
            ->withPivot(['joined_at', 'left_at'])
            ->using($pivot)
            ->withTimestamps();

        return $relation;
    }

    // ==================== WRESTLER RELATIONSHIPS ====================

    /**
     * Get all wrestlers that have ever been part of the stable.
     *
     * @return BelongsToMany<Wrestler, static, StableMember>
     *                                                       A relationship instance for accessing all wrestlers
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
        /** @var BelongsToMany<Wrestler, static, StableMember> $relation */
        $relation = $this->morphedByMany(Wrestler::class, 'member', 'stables_members')
            ->withPivot(['joined_at', 'left_at'])
            ->using(StableMember::class)
            ->withTimestamps();

        return $relation;
    }

    /**
     * Get wrestlers currently part of the stable.
     *
     * Returns wrestlers who are active members (no 'left_at' timestamp).
     *
     * @return BelongsToMany<Wrestler, static, StableMember>
     *                                                       A relationship instance for accessing current wrestlers
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
        /** @var BelongsToMany<Wrestler, static, StableMember> $relation */
        $relation = $this->morphedByMany(Wrestler::class, 'member', 'stables_members')
            ->withPivot(['joined_at', 'left_at'])
            ->using(StableMember::class)
            ->withTimestamps()
            ->wherePivotNull('left_at');

        return $relation;
    }

    /**
     * Get wrestlers who were previously part of the stable.
     *
     * Returns wrestlers who have left the stable (have a 'left_at' timestamp).
     *
     * @return BelongsToMany<Wrestler, static, StableMember>
     *                                                       A relationship instance for accessing previous wrestlers
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
        /** @var BelongsToMany<Wrestler, static, StableMember> $relation */
        $relation = $this->morphedByMany(Wrestler::class, 'member', 'stables_members')
            ->withPivot(['joined_at', 'left_at'])
            ->using(StableMember::class)
            ->withTimestamps()
            ->wherePivotNotNull('left_at');

        return $relation;
    }

    // ==================== TAG TEAM RELATIONSHIPS ====================

    /**
     * Get all tag teams that have ever been part of the stable.
     *
     * @return BelongsToMany<TagTeam, static, StableMember>
     *                                                      A relationship instance for accessing all tag teams
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
        /** @var BelongsToMany<TagTeam, static, StableMember> $relation */
        $relation = $this->morphedByMany(TagTeam::class, 'member', 'stables_members')
            ->withPivot(['joined_at', 'left_at'])
            ->using(StableMember::class)
            ->withTimestamps();

        return $relation;
    }

    /**
     * Get tag teams currently part of the stable.
     *
     * Returns tag teams who are active members (no 'left_at' timestamp).
     *
     * @return BelongsToMany<TagTeam, static, StableMember>
     *                                                      A relationship instance for accessing current tag teams
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
        /** @var BelongsToMany<TagTeam, static, StableMember> $relation */
        $relation = $this->morphedByMany(TagTeam::class, 'member', 'stables_members')
            ->withPivot(['joined_at', 'left_at'])
            ->using(StableMember::class)
            ->withTimestamps()
            ->wherePivotNull('left_at');

        return $relation;
    }

    /**
     * Get tag teams who were previously part of the stable.
     *
     * Returns tag teams who have left the stable (have a 'left_at' timestamp).
     *
     * @return BelongsToMany<TagTeam, static, StableMember>
     *                                                      A relationship instance for accessing previous tag teams
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
        /** @var BelongsToMany<TagTeam, static, StableMember> $relation */
        $relation = $this->morphedByMany(TagTeam::class, 'member', 'stables_members')
            ->withPivot(['joined_at', 'left_at'])
            ->using(StableMember::class)
            ->withTimestamps()
            ->wherePivotNotNull('left_at');

        return $relation;
    }
}
