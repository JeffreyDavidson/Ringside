<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Lifecycle\ActivityPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Contract for models that have activity periods (activation/deactivation cycles).
 *
 * This contract defines the interface for models that can be activated and deactivated
 * over time, tracking periods of activity with start and end dates.
 *
 * @template TDeclaringModel of Model
 */
interface HasActivityPeriods
{
    /**
     * Check if the model is currently activated/active.
     *
     * @return bool True if the model has an active period without an end date
     */
    public function isCurrentlyActive(): bool;

    /**
     * Get all activity periods for this model.
     *
     * @return MorphMany<ActivityPeriod, TDeclaringModel>
     */
    public function activityPeriods(): MorphMany;

    /**
     * Get the current activity period.
     *
     * @return MorphOne<ActivityPeriod, TDeclaringModel>
     */
    public function currentActivityPeriod(): MorphOne;

    /**
     * Get a future activity period that has not started or ended.
     *
     * @return MorphOne<ActivityPeriod, TDeclaringModel>
     */
    public function futureActivityPeriod(): MorphOne;
}
