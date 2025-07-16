<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Ankurk91\Eloquent\HasBelongsToOne;
use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Stables\Stable;
use App\Models\Stables\StableTagTeam;
use App\Models\Stables\StableWrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use InvalidArgumentException;

/**
 * Provides relationship accessors and utilities for models that can belong to one or more stables.
 *
 * This trait enables models to participate in stable memberships with proper
 * tracking of join/leave dates through pivot models. It provides methods to
 * access current, previous, and all stable relationships.
 *
 * @template TPivotModel of Pivot The pivot model for stable relationships
 * @template TModel of Model The model that can join stables
 *
 * @phpstan-require-implements CanBeAStableMember<TPivotModel, TModel>
 *
 * @example
 * ```php
 * class Wrestler extends Model implements CanBeAStableMember
 * {
 *     use CanJoinStables;
 * }
 *
 * $wrestler = Wrestler::find(1);
 * $allStables = $wrestler->stables;
 * $currentStable = $wrestler->currentStable;
 * ```
 */
trait CanJoinStables
{
    use HasBelongsToOne;

    /**
     * The resolved stable pivot model class name.
     *
     * @var class-string<TPivotModel>|null
     */
    protected static ?string $resolvedStablePivotModel = null;

    /**
     * Get the name of the pivot table for the stable relationship.
     *
     * Returns the appropriate pivot table based on the model type.
     * Uses separate tables for wrestlers and tag teams.
     *
     * @return string The pivot table name
     */
    protected function getStablePivotTable(): string
    {
        $morphClass = $this->getMorphClass();

        return match ($morphClass) {
            'wrestler', 'App\Models\Wrestlers\Wrestler' => 'stables_wrestlers',
            'tag_team', 'tagTeam', 'App\Models\TagTeams\TagTeam' => 'stables_tag_teams',
            default => throw new InvalidArgumentException("Unknown stable member type: {$morphClass}"),
        };
    }

    /**
     * Define a many-to-many relationship between the model and stables.
     *
     * Returns all stable relationships regardless of their current status
     * (active or completed). Includes pivot data for join/leave tracking.
     *
     * @return BelongsToMany<Stable, static, TPivotModel>
     *                                                    A relationship instance for accessing all stables
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allStables = $wrestler->stables;
     * $stableCount = $wrestler->stables()->count();
     * ```
     */
    public function stables(): BelongsToMany
    {
        $table = $this->getStablePivotTable();
        $morphClass = $this->getMorphClass();
        $foreignKey = match ($morphClass) {
            'wrestler', 'App\Models\Wrestlers\Wrestler' => 'wrestler_id',
            'tag_team', 'tagTeam', 'App\Models\TagTeams\TagTeam' => 'tag_team_id',
            default => throw new InvalidArgumentException("Unknown stable member type: {$morphClass}"),
        };

        $pivotClass = match ($morphClass) {
            'wrestler', 'App\Models\Wrestlers\Wrestler' => StableWrestler::class,
            'tag_team', 'tagTeam', 'App\Models\TagTeams\TagTeam' => StableTagTeam::class,
            default => throw new InvalidArgumentException("Unknown stable member type: {$morphClass}"),
        };

        /** @var BelongsToMany<Stable, static> $relation */
        $relation = $this->belongsToMany(
            Stable::class,
            $table,
            $foreignKey,
            'stable_id'
        )
            ->using($pivotClass)
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();

        return $relation;
    }

    /**
     * Define a one-to-one relationship for the current stable.
     *
     * Uses the `BelongsToOne` relationship to identify the currently joined stable
     * (i.e., where `left_at` is null).
     *
     * @return BelongsToOne
     *                      A relationship instance for accessing the current stable
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $currentStable = $wrestler->currentStable;
     *
     * if ($wrestler->currentStable()->exists()) {
     *     echo "Wrestler is currently in a stable";
     * }
     * ```
     */
    public function currentStable(): BelongsToOne
    {
        $table = $this->getStablePivotTable();
        $morphClass = $this->getMorphClass();
        $foreignKey = match ($morphClass) {
            'wrestler', 'App\Models\Wrestlers\Wrestler' => 'wrestler_id',
            'tag_team', 'tagTeam', 'App\Models\TagTeams\TagTeam' => 'tag_team_id',
            default => throw new InvalidArgumentException("Unknown stable member type: {$morphClass}"),
        };

        $pivotClass = match ($morphClass) {
            'wrestler', 'App\Models\Wrestlers\Wrestler' => StableWrestler::class,
            'tag_team', 'tagTeam', 'App\Models\TagTeams\TagTeam' => StableTagTeam::class,
            default => throw new InvalidArgumentException("Unknown stable member type: {$morphClass}"),
        };

        return $this->belongsToOne(
            Stable::class,
            $table,
            $foreignKey,
            'stable_id'
        )
            ->using($pivotClass)
            ->wherePivotNull('left_at')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * Get all stables the model previously belonged to.
     *
     * Returns stables that the model has left (where 'left_at' is not null).
     * These represent completed stable memberships.
     *
     * @return BelongsToMany<Stable, static, TPivotModel>
     *                                                    A relationship instance for accessing previous stables
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $formerStables = $wrestler->previousStables;
     * $stableHistory = $wrestler->previousStables()->orderBy('pivot_left_at', 'desc')->get();
     * ```
     */
    public function previousStables(): BelongsToMany
    {
        $table = $this->getStablePivotTable();
        $morphClass = $this->getMorphClass();
        $foreignKey = match ($morphClass) {
            'wrestler', 'App\Models\Wrestlers\Wrestler' => 'wrestler_id',
            'tag_team', 'tagTeam', 'App\Models\TagTeams\TagTeam' => 'tag_team_id',
            default => throw new InvalidArgumentException("Unknown stable member type: {$morphClass}"),
        };

        $pivotClass = match ($morphClass) {
            'wrestler', 'App\Models\Wrestlers\Wrestler' => StableWrestler::class,
            'tag_team', 'tagTeam', 'App\Models\TagTeams\TagTeam' => StableTagTeam::class,
            default => throw new InvalidArgumentException("Unknown stable member type: {$morphClass}"),
        };

        /** @var BelongsToMany<Stable, static> $relation */
        $relation = $this->belongsToMany(
            Stable::class,
            $table,
            $foreignKey,
            'stable_id'
        )
            ->using($pivotClass)
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps()
            ->wherePivotNotNull('left_at');

        return $relation;
    }

    /**
     * Determine whether the model is not currently a member of the given stable.
     *
     * Checks if the model's current stable is different from the provided stable.
     * Returns true if the model is not in the specified stable.
     *
     * @param  Stable  $stable  The stable to check against
     * @return bool True if the model is not in the specified stable, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $stable = Stable::find(1);
     *
     * if ($wrestler->isNotCurrentlyInStable($stable)) {
     *     echo "Wrestler is not in this stable";
     * }
     * ```
     */
    public function isNotCurrentlyInStable(Stable $stable): bool
    {
        $currentStable = $this->currentStable;

        if (! $currentStable) {
            return true;
        }

        /** @phpstan-ignore-next-line */
        return method_exists($currentStable, 'isNot') && $currentStable->isNot($stable);
    }
}
