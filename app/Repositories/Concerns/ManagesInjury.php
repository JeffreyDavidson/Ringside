<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use App\Models\Contracts\Injurable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * Trait for managing injury operations in repositories.
 *
 * This trait provides standardized methods for creating and ending injuries
 * across different repository classes. It works with any model that implements
 * the Injurable contract, ensuring consistent injury management behavior.
 *
 * Injury periods represent time ranges when an entity is injured and unable
 * to participate in matches or activities. Each injury has a start date and
 * optionally an end date for tracking recovery.
 *
 * @template TInjury of \Illuminate\Database\Eloquent\Model
 * @template TModel of Injurable&\Illuminate\Database\Eloquent\Model
 *
 * @see Injurable For the required model contract
 * @see IsInjurable For the model trait implementation
 *
 * @example
 * ```php
 * class WrestlerRepository extends BaseRepository
 * {
 *     use ManagesInjury;
 *
 *     public function injure(Wrestler $wrestler, Carbon $injuryDate): void
 *     {
 *         $this->createInjury($wrestler, $injuryDate);
 *     }
 *
 *     public function clearInjury(Wrestler $wrestler, Carbon $recoveryDate): void
 *     {
 *         $this->endInjury($wrestler, $recoveryDate);
 *     }
 * }
 * ```
 */
trait ManagesInjury
{
    /**
     * Create an injury for the given model.
     *
     * Creates a new injury record for the model starting at the specified date.
     * The injury will be considered active until it is explicitly ended using
     * the endInjury method when the entity recovers.
     *
     * @param  Injurable<Model, Model>  $model  The model to injure
     * @param  Carbon  $startDate  The date when the injury begins
     *
     * @throws QueryException If the injury creation fails
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $this->createInjury($wrestler, Carbon::parse('2024-01-15'));
     * ```
     */
    public function createInjury(Injurable $model, Carbon $startDate): void
    {
        $model->injuries()->create(['started_at' => $startDate->toDateTimeString()]);
    }

    /**
     * End the current active injury for the given model.
     *
     * Finds the currently active injury (where ended_at is null) and sets
     * the ended_at timestamp to the specified date. This represents recovery
     * from injury. If no active injury exists, this method will do nothing.
     *
     * @param  Injurable<Model, Model>  $model  The model whose injury should be ended
     * @param  Carbon  $endDate  The date when the injury ends (recovery date)
     *
     * @throws QueryException If the injury update fails
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $this->endInjury($wrestler, Carbon::parse('2024-02-15'));
     * ```
     */
    public function endInjury(Injurable $model, Carbon $endDate): void
    {
        $currentInjury = $model->currentInjury()->first();

        if ($currentInjury) {
            $currentInjury->update(['ended_at' => $endDate->toDateTimeString()]);
        }
    }
}
