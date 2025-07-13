<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\CanBeManaged;
use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Contract for models that can be managed.
 *
 * This interface defines the basic contract for any model that can have managers.
 * It provides a standard way to access manager relationships across different
 * model types while maintaining type safety through generics.
 *
 * Models implementing this interface should also use the CanBeManaged trait
 * to get the complete management functionality implementation.
 *
 * @template-covariant TPivotModel of Pivot The pivot model for the manager relationship
 * @template-covariant TModel of Model The model that can be managed
 *
 * @see CanBeManaged For the trait implementation
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Manageable
 * {
 *     use CanBeManaged;
 * }
 * ```
 */
interface Manageable
{
    /**
     * Get all managers that have ever been associated with the model.
     *
     * This method should return a BelongsToMany relationship that provides access
     * to all manager records associated with the model, including both current
     * and former managers. This represents the full historical record of all
     * manager relationships.
     *
     * @return BelongsToMany<Manager, TModel, TPivotModel>
     *                                                     A relationship instance for accessing all managers
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allManagers = $wrestler->managers;
     * $managerCount = $wrestler->managers()->count();
     * ```
     */
    public function managers(): BelongsToMany;

    /**
     * Get managers currently hired.
     *
     * This method should return a BelongsToMany relationship for managers
     * who are actively associated with the model (i.e., without a 'fired_at' date).
     *
     * @return BelongsToMany<Manager, TModel, TPivotModel>
     *                                                     A relationship instance for accessing current managers
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $currentManagers = $wrestler->currentManagers;
     *
     * if ($wrestler->currentManagers()->exists()) {
     *     echo "Wrestler has active managers";
     * }
     * ```
     */
    public function currentManagers(): BelongsToMany;

    /**
     * Get previously hired managers who have since been removed.
     *
     * This method should return a BelongsToMany relationship for managers
     * who were once associated with the model but are no longer hired
     * (i.e., have a 'fired_at' date).
     *
     * @return BelongsToMany<Manager, TModel, TPivotModel>
     *                                                     A relationship instance for accessing previous managers
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $formerManagers = $wrestler->previousManagers;
     * $managerHistory = $wrestler->previousManagers()->orderBy('fired_at', 'desc')->get();
     * ```
     */
    public function previousManagers(): BelongsToMany;
}
