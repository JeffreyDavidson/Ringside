<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Simple trait for models that have manager relationships.
 *
 * This trait provides basic current/previous manager accessor methods
 * as a lightweight alternative to the more comprehensive CanBeManaged trait.
 * It assumes the implementing model already has a managers() relationship method.
 *
 * @template TPivotModel of Pivot The pivot model for manager relationships
 *
 * @phpstan-require-implements \App\Models\Contracts\Manageable<TPivotModel, static>
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Manageable
 * {
 *     use HasManagers;
 *
 *     public function managers(): BelongsToMany
 *     {
 *         return $this->belongsToMany(Manager::class)->withPivot(['hired_at', 'fired_at']);
 *     }
 * }
 * ```
 */
trait HasManagers
{
    /**
     * The base managers relationship - must be implemented by the using model.
     *
     * @return BelongsToMany<Manager, static, TPivotModel>
     */
    abstract public function managers(): BelongsToMany;

    /**
     * Get currently active managers for the model.
     *
     * Returns managers where the 'fired_at' pivot column is null,
     * indicating they are still actively managing the model.
     *
     * @return BelongsToMany<Manager, static, TPivotModel>
     */
    public function currentManagers(): BelongsToMany
    {
        return $this->managers()
            ->wherePivotNull('fired_at');
    }

    /**
     * Get previously assigned managers for the model.
     *
     * Returns managers where the 'fired_at' pivot column is not null,
     * indicating they are no longer managing the model.
     *
     * @return BelongsToMany<Manager, static, TPivotModel>
     */
    public function previousManagers(): BelongsToMany
    {
        return $this->managers()
            ->wherePivotNotNull('fired_at');
    }
}
