<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Models\Wrestlers\Wrestler;

/**
 * Custom query builder for the Wrestler model.
 *
 * Provides specialized query methods for filtering wrestlers by their employment status,
 * including available, injured, retired, released, suspended, and unemployed wrestlers.
 * This builder makes it easy to filter wrestlers based on their current availability
 * and employment conditions.
 *
 * @template TModel of Wrestler
 *
 * @extends IndividualBuilder<TModel>
 *
 * @example
 * ```php
 * // Get all available wrestlers
 * $availableWrestlers = Wrestler::query()->available()->get();
 *
 * // Get injured wrestlers who need to be cleared
 * $injuredWrestlers = Wrestler::query()->injured()->get();
 *
 * // Chain multiple conditions
 * $availableWrestlers = Wrestler::query()
 *     ->available()
 *     ->get();
 * ```
 */
class WrestlerBuilder extends IndividualBuilder {}
