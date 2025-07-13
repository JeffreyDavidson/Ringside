<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Contract for models that can be members of stables.
 *
 * This interface defines the basic contract for any model that can be a member
 * of stables. It provides a standard way to access stable relationships
 * across different model types while maintaining type safety through generics.
 *
 * Models implementing this interface should also use the CanJoinStables trait
 * to get the complete stable membership functionality implementation.
 *
 * @template TPivotModel of Pivot The pivot model for the stable relationship
 * @template TModel of Model The model that can be a stable member
 *
 * @see CanJoinStables For the trait implementation
 *
 * @example
 * ```php
 * class Wrestler extends Model implements CanBeAStableMember
 * {
 *     use CanJoinStables;
 * }
 * ```
 */
interface CanBeAStableMember
{
    /**
     * Get all stables this model has been a part of.
     *
     * This method should return a BelongsToMany relationship that provides access
     * to all stable records associated with the model, regardless of status.
     *
     * @return BelongsToMany<Stable, TModel, TPivotModel>
     *                                                    A relationship instance for accessing all stables
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allStables = $wrestler->stables;
     * $stableCount = $wrestler->stables()->count();
     * ```
     */
    public function stables(): BelongsToMany;

    /**
     * Get the stable the model currently belongs to.
     *
     * This method should return a BelongsToOne relationship for the currently
     * active stable membership.
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
    public function currentStable(): BelongsToOne;
}
