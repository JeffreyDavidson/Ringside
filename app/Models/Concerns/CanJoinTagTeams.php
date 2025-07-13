<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Ankurk91\Eloquent\HasBelongsToOne;
use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\Contracts\CanBeATagTeamMember;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use RuntimeException;

/**
 * Provides functionality for models (e.g., Wrestlers) to join and manage relationships with tag teams.
 *
 * This trait enables models to participate in tag team memberships with proper
 * tracking of join/leave dates through pivot models. It provides methods to
 * access current, previous, and all tag team relationships.
 *
 * @template TPivotModel of Pivot The pivot model for tag team relationships
 * @template TModel of Model The model that can join tag teams
 *
 * @phpstan-require-implements CanBeATagTeamMember<TPivotModel, TModel>
 *
 * @example
 * ```php
 * class Wrestler extends Model implements CanBeATagTeamMember
 * {
 *     use CanJoinTagTeams;
 * }
 *
 * $wrestler = Wrestler::find(1);
 * $allTagTeams = $wrestler->tagTeams;
 * $currentTagTeam = $wrestler->currentTagTeam;
 * ```
 */
trait CanJoinTagTeams
{
    use HasBelongsToOne;

    /**
     * The resolved tag team pivot model class name.
     *
     * @var class-string<TPivotModel>|null
     */
    protected static ?string $resolvedTagTeamPivotModel = null;

    /**
     * Determine the pivot table name dynamically.
     *
     * By default, returns a convention-based name like 'tag_team_wrestlers'.
     * Uses Laravel's alphabetical sorting convention for consistency.
     *
     * @return string The pivot table name
     *
     * @example
     * For a Wrestler model, this returns 'tag_teams_wrestlers'
     */
    protected function getTagTeamPivotTable(): string
    {
        $related = 'tag_teams';
        $self = str(class_basename(static::class))->snake()->plural();

        // Sort alphabetically to match Laravel's convention
        return collect([$related, $self])->sort()->implode('_');
    }

    /**
     * Get all tag teams the model has been a part of.
     *
     * Returns all tag team relationships regardless of their current status
     * (active or completed). Includes pivot data for join/leave tracking.
     *
     * @return BelongsToMany<TagTeam, static, TPivotModel>
     *                                                     A relationship instance for accessing all tag teams
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allTagTeams = $wrestler->tagTeams;
     * $tagTeamCount = $wrestler->tagTeams()->count();
     * ```
     */
    public function tagTeams(): BelongsToMany
    {
        /** @var BelongsToMany<TagTeam, static, TPivotModel> $relation */
        $relation = $this->belongsToMany(TagTeam::class, $this->getTagTeamPivotTable())
            ->withPivot(['joined_at', 'left_at'])
            ->using($this->resolveTagTeamPivotModel())
            ->withTimestamps();

        return $relation;
    }

    /**
     * Get all previous tag teams the model was part of.
     *
     * Returns tag teams that the model has left (where 'left_at' is set).
     * These represent completed tag team memberships.
     *
     * @return BelongsToMany<TagTeam, static, TPivotModel>
     *                                                     A relationship instance for accessing previous tag teams
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $formerTagTeams = $wrestler->previousTagTeams;
     * $tagTeamHistory = $wrestler->previousTagTeams()->orderBy('pivot_left_at', 'desc')->get();
     * ```
     */
    public function previousTagTeams(): BelongsToMany
    {
        /** @var BelongsToMany<TagTeam, static, TPivotModel> $relation */
        $relation = $this->belongsToMany(TagTeam::class, $this->getTagTeamPivotTable())
            ->withPivot(['joined_at', 'left_at'])
            ->using($this->resolveTagTeamPivotModel())
            ->withTimestamps()
            ->wherePivotNotNull('left_at');

        return $relation;
    }

    /**
     * Get the most recent previous tag team the model was part of.
     *
     * Returns the most recently left tag team based on the 'left_at' date.
     * Uses BelongsToOne for a single result.
     *
     * @return BelongsToOne
     *                      A relationship instance for accessing the most recent previous tag team
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $lastTagTeam = $wrestler->previousTagTeam;
     *
     * if ($wrestler->previousTagTeam()->exists()) {
     *     echo "Last tag team: " . $wrestler->previousTagTeam->name;
     * }
     * ```
     */
    public function previousTagTeam(): BelongsToOne
    {
        return $this->belongsToOne(TagTeam::class, $this->getTagTeamPivotTable())
            ->wherePivotNotNull('left_at')
            ->withPivot(['joined_at', 'left_at'])
            ->orderByPivot('left_at', 'desc')
            ->withTimestamps();
    }

    /**
     * Get the current tag team the model belongs to.
     *
     * Returns the active tag team membership (where 'left_at' is null).
     * Uses BelongsToOne since a model should only have one current tag team.
     *
     * @return BelongsToOne
     *                      A relationship instance for accessing the current tag team
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $currentTagTeam = $wrestler->currentTagTeam;
     *
     * if ($wrestler->currentTagTeam()->exists()) {
     *     echo "Current tag team: " . $wrestler->currentTagTeam->name;
     * }
     * ```
     */
    public function currentTagTeam(): BelongsToOne
    {
        return $this->belongsToOne(TagTeam::class, $this->getTagTeamPivotTable())
            ->wherePivotNull('left_at')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * Determine if the model is currently part of an active tag team.
     *
     * Checks if there is an active tag team relationship (where 'left_at' is null).
     * This is more efficient than loading the relationship just to check existence.
     *
     * @return bool True if the model is currently in a tag team, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->isAMemberOfCurrentTagTeam()) {
     *     echo "Wrestler is currently in a tag team";
     * }
     * ```
     */
    public function isAMemberOfCurrentTagTeam(): bool
    {
        return $this->belongsToMany(TagTeam::class, $this->getTagTeamPivotTable())
            ->wherePivotNull('left_at')
            ->exists();
    }

    /**
     * Resolve the pivot model class for tag team relationships.
     *
     * This method automatically determines the pivot model class based on naming
     * conventions. For example, if the parent model is 'Wrestler', it will look for
     * a 'TagTeamWrestler' pivot model class.
     *
     * @return class-string<TPivotModel> The fully qualified class name of the pivot model
     *
     * @throws RuntimeException If the resolved model class doesn't exist
     *
     * @example
     * For a 'Wrestler' model, this will resolve to 'App\\Models\\TagTeamWrestler'
     */
    protected function resolveTagTeamPivotModel(): string
    {
        if (static::$resolvedTagTeamPivotModel !== null) {
            return static::$resolvedTagTeamPivotModel;
        }

        return $this->resolveTagTeamPivotModelInternal();
    }

    /**
     * Internal method to resolve tag team pivot model class.
     *
     * @return class-string<TPivotModel>
     */
    private function resolveTagTeamPivotModelInternal(): string
    {
        $declaring = static::class;
        $baseName = class_basename($declaring);

        // Build the related model class name - tag team pivots are in the TagTeams namespace
        $relatedModelName = 'TagTeam'.$baseName;
        $resolved = 'App\\Models\\TagTeams\\'.$relatedModelName;

        if (! class_exists($resolved)) {
            throw new RuntimeException("Related pivot model [{$resolved}] not found for [{$declaring}]. Override the resolution method or ensure the class exists.");
        }

        /** @var class-string<TPivotModel> */
        return $resolved;
    }

    /**
     * Override the resolved pivot model class for testing or customization.
     *
     * @param  class-string<TPivotModel>  $class  The fully qualified class name to use
     *
     * @example
     * ```php
     * // In a test:
     * Wrestler::fakeTagTeamPivotModel(MockTagTeamWrestler::class);
     * ```
     */
    public static function fakeTagTeamPivotModel(string $class): void
    {
        static::$resolvedTagTeamPivotModel = $class;
    }
}
