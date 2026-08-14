<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contracts\Manageable;
use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Provides manager relationship support for models that can be managed by `Manager` instances.
 *
 * This trait enables models to have management relationships with proper
 * tracking of hire/leave dates through pivot models. It provides methods to
 * access current, previous, and all manager relationships.
 *
 * @template TPivotModel of Pivot The pivot model for manager relationships
 * @template TModel of Model The model that can be managed
 *
 * @phpstan-require-implements Manageable<TPivotModel, TModel>
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Manageable
 * {
 *     use CanBeManaged;
 * }
 *
 * $wrestler = Wrestler::find(1);
 * $allManagers = $wrestler->managers;
 * $currentManagers = $wrestler->currentManagers;
 * ```
 */
trait CanBeManaged
{
    abstract protected function managerAssignmentTable(): string;

    /** @return class-string<TPivotModel> */
    abstract protected function managerAssignmentPivotModel(): string;

    /**
     * Define a BelongsToMany relationship to the model's managers.
     *
     * Returns all manager relationships regardless of their current status
     * (active or completed). Includes pivot data for hire/leave tracking.
     *
     * @return BelongsToMany<Manager, static, TPivotModel>
     *                                                     A relationship instance for accessing all managers
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allManagers = $wrestler->managers;
     * $managerCount = $wrestler->managers()->count();
     * ```
     */
    public function managers(): BelongsToMany
    {
        /** @var BelongsToMany<Manager, static, TPivotModel> $relation */
        $relation = $this->belongsToMany(Manager::class, $this->managerAssignmentTable())
            ->using($this->managerAssignmentPivotModel())
            ->withPivot(['hired_at', 'fired_at'])
            ->withTimestamps();

        return $relation;
    }

    /**
     * Retrieve all currently assigned managers for the model.
     *
     * These are managers who have been hired and have not yet been marked as having left
     * (i.e., the `fired_at` column on the pivot table is null).
     *
     * @return BelongsToMany<Manager, static, TPivotModel>
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
    public function currentManagers(): BelongsToMany
    {
        /** @var BelongsToMany<Manager, static, TPivotModel> $relation */
        $relation = $this->belongsToMany(Manager::class, $this->managerAssignmentTable())
            ->using($this->managerAssignmentPivotModel())
            ->withPivot(['hired_at', 'fired_at'])
            ->withTimestamps()
            ->wherePivotNull('fired_at');

        return $relation;
    }

    /**
     * Retrieve all previously assigned managers for the model.
     *
     * These are managers who were once hired but are no longer assigned to the model
     * (i.e., the `fired_at` column on the pivot table is not null).
     *
     * @return BelongsToMany<Manager, static, TPivotModel>
     *                                                     A relationship instance for accessing previous managers
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $formerManagers = $wrestler->previousManagers;
     * $managerHistory = $wrestler->previousManagers()->orderBy('pivot_fired_at', 'desc')->get();
     * ```
     */
    public function previousManagers(): BelongsToMany
    {
        /** @var BelongsToMany<Manager, static, TPivotModel> $relation */
        $relation = $this->belongsToMany(Manager::class, $this->managerAssignmentTable())
            ->using($this->managerAssignmentPivotModel())
            ->withPivot(['hired_at', 'fired_at'])
            ->withTimestamps()
            ->wherePivotNotNull('fired_at');

        return $relation;
    }
}
