<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Provides generic relationship logic for tracking which models are managed by the current model.
 *
 * This trait assumes a many-to-many relationship is established through a pivot table
 * with `hired_at` and `fired_at` timestamps to indicate when the manager was associated
 * with and later removed from the managed model.
 *
 * Typical use cases include models like `Manager` managing `Wrestler` or `TagTeam`.
 * The trait provides methods to get all managed entities, current ones, and previous ones.
 *
 * @template TManagedModel of \Illuminate\Database\Eloquent\Model The model being managed
 *
 * @example
 * ```php
 * class Manager extends Model
 * {
 *     use ManagesEntities;
 *
 *     public function wrestlers(): BelongsToMany
 *     {
 *         return $this->getManagedRelation(Wrestler::class, 'wrestlers_managers');
 *     }
 * }
 * ```
 */
trait ManagesEntities
{
    /**
     * Define a base many-to-many relationship with a managed model.
     *
     * Returns all instances of the given model that have ever been managed,
     * regardless of whether the management relationship is still active.
     * The relationship includes pivot data for `hired_at` and `fired_at` timestamps.
     *
     * @param  class-string<TManagedModel>  $class  The fully qualified class name of the managed model
     * @param  string  $pivotTable  The pivot table name used for the management relationship
     * @return BelongsToMany<TManagedModel, $this>
     *                                             A relationship instance for accessing all managed entities
     *
     * @example
     * ```php
     * // In a Manager model:
     * public function wrestlers(): BelongsToMany
     * {
     *     return $this->getManagedRelation(Wrestler::class, 'wrestlers_managers');
     * }
     * ```
     */
    protected function getManagedRelation(string $class, string $pivotTable): BelongsToMany
    {
        /** @var BelongsToMany<TManagedModel, $this> $relation */
        $relation = $this->belongsToMany($class, $pivotTable)
            ->withPivot(['hired_at', 'fired_at'])
            ->withTimestamps();

        return $relation;
    }

    /**
     * Get currently managed models.
     *
     * Returns entities that are actively being managed (i.e., those with a
     * `hired_at` timestamp but no `fired_at` timestamp).
     *
     * @param  class-string<TManagedModel>  $class  The managed model class
     * @param  string  $pivotTable  The pivot table name
     * @return BelongsToMany<TManagedModel, $this>
     *                                             A relationship instance for accessing currently managed entities
     *
     * @example
     * ```php
     * // In a Manager model:
     * public function currentWrestlers(): BelongsToMany
     * {
     *     return $this->currentManaged(Wrestler::class, 'wrestlers_managers');
     * }
     * ```
     */
    protected function currentManaged(string $class, string $pivotTable): BelongsToMany
    {
        /** @var BelongsToMany<TManagedModel, $this> $relation */
        $relation = $this->belongsToMany($class, $pivotTable)
            ->withPivot(['hired_at', 'fired_at'])
            ->withTimestamps()
            ->wherePivotNull('fired_at');

        return $relation;
    }

    /**
     * Get previously managed models.
     *
     * Returns entities that were once managed but are no longer (i.e., those
     * with a non-null `fired_at` timestamp).
     *
     * @param  class-string<TManagedModel>  $class  The managed model class
     * @param  string  $pivotTable  The pivot table name
     * @return BelongsToMany<TManagedModel, $this>
     *                                             A relationship instance for accessing previously managed entities
     *
     * @example
     * ```php
     * // In a Manager model:
     * public function previousWrestlers(): BelongsToMany
     * {
     *     return $this->previousManaged(Wrestler::class, 'wrestlers_managers');
     * }
     * ```
     */
    protected function previousManaged(string $class, string $pivotTable): BelongsToMany
    {
        /** @var BelongsToMany<TManagedModel, $this> $relation */
        $relation = $this->belongsToMany($class, $pivotTable)
            ->withPivot(['hired_at', 'fired_at'])
            ->withTimestamps()
            ->wherePivotNotNull('fired_at');

        return $relation;
    }
}
