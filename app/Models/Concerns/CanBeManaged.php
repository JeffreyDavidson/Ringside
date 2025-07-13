<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contracts\Manageable;
use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use RuntimeException;

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
    /**
     * The resolved manager pivot model class name.
     *
     * @var class-string<TPivotModel>|null
     */
    protected static ?string $resolvedManagerPivotModel = null;

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
        $relation = $this->belongsToMany(Manager::class, $this->getManagersPivotTable())
            ->using($this->resolveManagersPivotModel())
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
        $relation = $this->belongsToMany(Manager::class, $this->getManagersPivotTable())
            ->using($this->resolveManagersPivotModel())
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
        $relation = $this->belongsToMany(Manager::class, $this->getManagersPivotTable())
            ->using($this->resolveManagersPivotModel())
            ->withPivot(['hired_at', 'fired_at'])
            ->withTimestamps()
            ->wherePivotNotNull('fired_at');

        return $relation;
    }

    /**
     * Get the name of the pivot table for the manager relationship.
     *
     * The default naming convention is `<model_plural>_managers`,
     * e.g., `wrestlers_managers` or `tag_teams_managers`.
     *
     * Override this method in the model to customize the pivot table name.
     *
     * @return string The pivot table name
     *
     * @example
     * For a Wrestler model, this returns 'wrestlers_managers'
     */
    protected function getManagersPivotTable(): string
    {
        return str(class_basename($this))
            ->plural()
            ->snake()
            ->append('_managers')
            ->toString(); // e.g., "wrestlers_managers" or "tag_teams_managers"
    }

    /**
     * Resolve the pivot model class for manager relationships.
     *
     * This method automatically determines the pivot model class based on naming
     * conventions. For example, if the parent model is 'Wrestler', it will look for
     * a 'WrestlerManager' pivot model class.
     *
     * @return class-string<TPivotModel> The fully qualified class name of the pivot model
     *
     * @throws RuntimeException If the resolved model class doesn't exist
     *
     * @example
     * For a 'Wrestler' model, this will resolve to 'App\\Models\\WrestlerManager'
     */
    protected function resolveManagersPivotModel(): string
    {
        if (static::$resolvedManagerPivotModel !== null) {
            return static::$resolvedManagerPivotModel;
        }

        return $this->resolveManagersPivotModelInternal();
    }

    /**
     * Internal method to resolve manager pivot model class.
     *
     * @return class-string<TPivotModel>
     */
    private function resolveManagersPivotModelInternal(): string
    {
        $declaring = static::class;
        $baseName = class_basename($declaring);

        // Build the related model class name by replacing only the class name, not the namespace
        $relatedModelName = $baseName.'Manager';
        $namespace = mb_substr($declaring, 0, mb_strrpos($declaring, '\\'));
        $resolved = $namespace.'\\'.$relatedModelName;

        if (! class_exists($resolved)) {
            throw new RuntimeException("Related pivot model [{$resolved}] not found for [{$declaring}]. Override the resolution method or ensure the class exists.");
        }

        /** @var class-string<TPivotModel> */
        return $resolved;
    }

    /**
     * Override the resolved pivot model class for testing or customization.
     *
     * @param  class-string<TPivotModel>|null  $class  The fully qualified class name to use, or null to reset
     *
     * @example
     * ```php
     * // In a test:
     * Wrestler::fakeManagerPivotModel(MockWrestlerManager::class);
     * // Reset:
     * Wrestler::fakeManagerPivotModel(null);
     * ```
     */
    public static function fakeManagerPivotModel(?string $class): void
    {
        static::$resolvedManagerPivotModel = $class;
    }
}
