<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use App\Enums\Shared\EmploymentStatus;
use App\Enums\Stables\StableStatus;
use App\Enums\Titles\TitleStatus;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Retirable;
use App\Models\Stables\Stable;
use App\Models\Titles\Title;
use BackedEnum;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * Trait for managing retirement operations in repositories.
 *
 * This trait provides standardized methods for creating and ending retirements
 * across different repository classes. It works with any model that implements
 * the Retirable contract, ensuring consistent retirement management behavior.
 *
 * For models that can be employed, retirement will automatically end current employment.
 * This ensures business rule compliance where retirement supersedes employment status.
 *
 * @template TRetirement of \Illuminate\Database\Eloquent\Model
 * @template TModel of Retirable&\Illuminate\Database\Eloquent\Model
 */
trait ManagesRetirement
{
    /**
     * Create a retirement for the given model.
     *
     * Creates a new retirement record for the model starting at the specified date,
     * ends any current employment, and updates the model's status field to reflect
     * the retired state. The retirement will be considered active until it is
     * explicitly ended using the endRetirement method (for comebacks).
     *
     * IMPORTANT: This method performs multiple database operations and should be
     * called within a database transaction to ensure data consistency.
     *
     * @template T of Retirable
     *
     * @param  T  $model  The model to retire
     * @param  Carbon  $startDate  The date when the retirement begins
     *
     * @throws QueryException If the retirement creation fails
     */
    public function createRetirement(Retirable $model, Carbon $startDate): void
    {
        // End current employment if active (retirement ends employment)
        if ($model instanceof Employable && $model->currentEmployment) { // @phpstan-ignore-line property.notFound
            $model->currentEmployment->update(['ended_at' => $startDate]);
        }

        // Create the retirement relationship
        $model->retirements()->create([
            'started_at' => $startDate,
        ]);

        // Update the status field to reflect retirement
        $retiredStatus = $this->getRetiredStatus($model);
        $model->update(['status' => $retiredStatus]);
    }

    /**
     * End the current active retirement for the given model.
     *
     * Finds the currently active retirement (where ended_at is null) and sets
     * the ended_at timestamp to the specified date, then updates the model's
     * status field to reflect the comeback from retirement. This represents a
     * comeback from retirement. If no active retirement exists, this method will do nothing.
     *
     * IMPORTANT: This method performs multiple database operations and should be
     * called within a database transaction to ensure data consistency.
     *
     * @template T of Retirable
     *
     * @param  T  $model  The model whose retirement should be ended
     * @param  Carbon  $endDate  The date when the retirement ends (comeback date)
     *
     * @throws QueryException If the retirement update fails
     */
    public function endRetirement(Retirable $model, Carbon $endDate): void
    {
        $currentRetirement = $model->currentRetirement()->first();

        if ($currentRetirement) {
            // End the retirement relationship
            $currentRetirement->update([
                'ended_at' => $endDate,
            ]);

            // Update the status field to reflect comeback from retirement
            // Note: We set to Released as the default post-retirement status
            // The calling action should handle setting the correct employment status
            $comebackStatus = $this->getComebackStatus($model);
            $model->update(['status' => $comebackStatus]);
        }
    }

    /**
     * Get the appropriate retired status for the given model type.
     *
     * @param  Retirable  $model  The model to get retired status for
     * @return BackedEnum The appropriate retired status
     */
    private function getRetiredStatus(Retirable $model): BackedEnum
    {
        return match (get_class($model)) {
            Stable::class => StableStatus::Retired,
            Title::class => TitleStatus::Inactive,
            default => EmploymentStatus::Retired,
        };
    }

    /**
     * Get the appropriate comeback status for the given model type.
     *
     * @param  Retirable  $model  The model to get comeback status for
     * @return BackedEnum The appropriate comeback status
     */
    private function getComebackStatus(Retirable $model): BackedEnum
    {
        return match (get_class($model)) {
            Stable::class => StableStatus::Inactive,
            Title::class => TitleStatus::Active,
            default => EmploymentStatus::Released,
        };
    }
}
