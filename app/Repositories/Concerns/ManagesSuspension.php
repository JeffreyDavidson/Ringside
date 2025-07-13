<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * Trait for managing suspension operations in repositories.
 *
 * This trait provides standardized methods for creating and ending suspensions
 * across different repository classes. It works with any model that implements
 * the Suspendable contract, ensuring consistent suspension management behavior.
 *
 * The trait handles the business logic for suspension operations while delegating
 * the actual relationship management to the model's implementation of the
 * Suspendable interface. Query methods like checking suspension status are
 * available directly on the model.
 *
 * @template TSuspension of \Illuminate\Database\Eloquent\Model
 * @template TModel of Suspendable&\Illuminate\Database\Eloquent\Model
 *
 * @see Suspendable For the required model contract
 * @see IsSuspendable For the model trait implementation
 *
 * @example
 * ```php
 * class WrestlerRepository extends BaseRepository
 * {
 *     use ManagesSuspension;
 *
 *     // Now has createSuspension() and endSuspension() methods
 * }
 *
 * $repository = new WrestlerRepository();
 * $wrestler = Wrestler::find(1);
 *
 * // Create a suspension
 * $repository->createSuspension($wrestler, now());
 *
 * // Check status using model methods
 * if ($wrestler->isSuspended()) {
 *     echo "Cannot compete";
 * }
 *
 * // End the suspension later
 * $repository->endSuspension($wrestler, now()->addDays(30));
 * ```
 */
trait ManagesSuspension
{
    /**
     * Create a suspension for the given model.
     *
     * Creates a new suspension record for the model starting at the specified date.
     * The suspension will be considered active until it is explicitly ended using
     * the endSuspension method.
     *
     * @param  Suspendable<Model, Model>  $model  The model to suspend
     * @param  Carbon  $startDate  The date when the suspension begins
     *
     * @throws QueryException If the suspension creation fails
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $this->createSuspension($wrestler, Carbon::parse('2024-01-15'));
     *
     * // The wrestler is now suspended starting from January 15, 2024
     * ```
     */
    public function createSuspension(Suspendable $model, Carbon $startDate): void
    {
        $model->suspensions()->create([
            'started_at' => $startDate,
        ]);
    }

    /**
     * End the current active suspension for the given model.
     *
     * Finds the currently active suspension (where ended_at is null) and sets
     * the ended_at timestamp to the specified date. If no active suspension
     * exists, this method will do nothing.
     *
     * @param  Suspendable<Model, Model>  $model  The model whose suspension should be ended
     * @param  Carbon  $endDate  The date when the suspension ends
     *
     * @throws QueryException If the suspension update fails
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $this->endSuspension($wrestler, Carbon::parse('2024-02-15'));
     *
     * // The wrestler's current suspension is now ended as of February 15, 2024
     * ```
     */
    public function endSuspension(Suspendable $model, Carbon $endDate): void
    {
        $currentSuspension = $model->currentSuspension()->first();

        if ($currentSuspension) {
            $currentSuspension->update([
                'ended_at' => $endDate,
            ]);
        }
    }
}
