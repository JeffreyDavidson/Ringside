<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Contracts\Employable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * Trait for managing employment operations in repositories.
 *
 * This trait provides standardized methods for creating and ending employment
 * across different repository classes. It works with any model that implements
 * the Employable contract, ensuring consistent employment management behavior.
 *
 * @template TEmployment of \Illuminate\Database\Eloquent\Model
 * @template TModel of Employable&\Illuminate\Database\Eloquent\Model
 */
trait ManagesEmployment
{
    /**
     * Create an employment record for the given model.
     *
     * Creates a new employment record for the model starting at the specified date
     * and updates the model's status field to reflect the employment state.
     * Uses updateOrCreate to ensure only one active employment exists at a time,
     * automatically ending any previous employment.
     *
     * IMPORTANT: This method performs multiple database operations and should be
     * called within a database transaction to ensure data consistency.
     *
     * @template T of Employable
     *
     * @param  T  $model  The model to employ
     * @param  Carbon  $startDate  The date when employment begins
     *
     * @throws QueryException If the employment creation fails
     *
     * @example
     * ```php
     * // CORRECT - Within transaction
     * DB::transaction(function () use ($wrestler, $date) {
     *     $repository->createEmployment($wrestler, $date);
     * });
     *
     * // INCORRECT - Not in transaction
     * $repository->createEmployment($wrestler, $date); // Risk of inconsistent state
     * ```
     */
    public function createEmployment(Employable $model, Carbon $startDate): void
    {
        // Create or update the employment relationship
        $model->employments()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $startDate->toDateTimeString()]
        );

        // Update the status field to reflect employment
        $model->update(['status' => EmploymentStatus::Employed]); // @phpstan-ignore-line method.notFound
    }

    /**
     * End the current active employment for the given model.
     *
     * Finds the currently active employment (where ended_at is null) and sets
     * the ended_at timestamp to the specified date, then updates the model's
     * status field to reflect the released state. If no active employment
     * exists, this method will do nothing.
     *
     * IMPORTANT: This method performs multiple database operations and should be
     * called within a database transaction to ensure data consistency.
     *
     * @template T of Employable
     *
     * @param  T  $model  The model whose employment should be ended
     * @param  Carbon  $endDate  The date when employment ends
     *
     * @throws QueryException If the employment update fails
     */
    public function endEmployment(Employable $model, Carbon $endDate): void
    {
        $currentEmployment = $model->currentEmployment()->first();

        if ($currentEmployment) {
            // End the employment relationship
            $currentEmployment->update([
                'ended_at' => $endDate,
            ]);

            // Update the status field to reflect released state
            $model->update(['status' => EmploymentStatus::Released]); // @phpstan-ignore-line method.notFound
        }
    }

    /**
     * Create a release record for the given model.
     *
     * This method provides a direct way to release an entity, ending their current
     * employment and updating their status to Released. This combines ending employment
     * with the proper status field synchronization.
     *
     * IMPORTANT: This method performs multiple database operations and should be
     * called within a database transaction to ensure data consistency.
     *
     * @template T of Employable
     *
     * @param  T  $model  The model to release
     * @param  Carbon  $releaseDate  The date when the release occurs
     *
     * @throws QueryException If the release operation fails
     */
    public function createRelease(Employable $model, Carbon $releaseDate): void
    {
        $this->endEmployment($model, $releaseDate);
    }

    /**
     * Create a reinstatement record for the given model.
     *
     * This method reinstates an entity by creating new employment starting at the
     * specified date and updating their status to Employed. This is typically used
     * after suspension or disciplinary action has ended.
     *
     * IMPORTANT: This method performs multiple database operations and should be
     * called within a database transaction to ensure data consistency.
     *
     * @template T of Employable
     *
     * @param  T  $model  The model to reinstate
     * @param  Carbon  $reinstatementDate  The date when reinstatement begins
     *
     * @throws QueryException If the reinstatement operation fails
     */
    public function createReinstatement(Employable $model, Carbon $reinstatementDate): void
    {
        $this->createEmployment($model, $reinstatementDate);
    }
}
