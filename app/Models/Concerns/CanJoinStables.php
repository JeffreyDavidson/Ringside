<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Ankurk91\Eloquent\HasBelongsToOne;
use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

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
     * Returns the polymorphic stable members table that handles all member types.
     *
     * @return string The pivot table name
     */
    protected function getStablePivotTable(): string
    {
        return 'stables_members';
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
        /** @var BelongsToMany<Stable, static, TPivotModel> $relation */
        $relation = $this->belongsToMany(
            Stable::class,
            'stables_members',
            'member_id',
            'stable_id'
        )
            ->where('stables_members.member_type', $this->getMorphClass())
            ->using($this->resolveStablePivotModel())
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
        return $this->belongsToOne(
            Stable::class,
            'stables_members',
            'member_id',
            'stable_id'
        )
            ->where('stables_members.member_type', $this->getMorphClass())
            ->using($this->resolveStablePivotModel())
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
        /** @var BelongsToMany<Stable, static, TPivotModel> $relation */
        $relation = $this->belongsToMany(
            Stable::class,
            'stables_members',
            'member_id',
            'stable_id'
        )
            ->where('stables_members.member_type', $this->getMorphClass())
            ->using($this->resolveStablePivotModel())
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps()
            ->wherePivot('joined_at', '<', now())
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

    /**
     * Resolve the pivot model class for stable relationships.
     *
     * All models now use the polymorphic 'StableMember' pivot model class.
     *
     * @return class-string<TPivotModel> The fully qualified class name of the pivot model
     *
     * @example
     * For any model, this will resolve to 'App\\Models\\Stables\\StableMember'
     */
    protected function resolveStablePivotModel(): string
    {
        if (static::$resolvedStablePivotModel !== null) {
            return static::$resolvedStablePivotModel;
        }

        /** @var class-string<TPivotModel> */
        return 'App\\Models\\Stables\\StableMember';
    }

    /**
     * Override the resolved pivot model class for testing or customization.
     *
     * @param  class-string<TPivotModel>  $class  The fully qualified class name to use
     *
     * @example
     * ```php
     * // In a test:
     * Wrestler::fakeStablePivotModel(MockStableMember::class);
     * ```
     */
    public static function fakeStablePivotModel(string $class): void
    {
        static::$resolvedStablePivotModel = $class;
    }
}
