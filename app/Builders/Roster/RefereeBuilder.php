<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\HasNameSearch;
use App\Models\Referees\Referee;

/**
 * Custom query builder for the Referee model.
 *
 * Provides specialized query methods for filtering referees by their employment status,
 * including available, injured, unemployed, retired, released, suspended, and future
 * employed referees. This builder enables easy filtering of referees based on their
 * current availability and employment conditions for match officiating.
 *
 * @template TModel of Referee
 *
 * @extends IndividualBuilder<TModel>
 *
 * @example
 * ```php
 * // Get all available referees
 * $availableReferees = Referee::query()->available()->get();
 *
 * // Get injured referees who need to be cleared
 * $injuredReferees = Referee::query()->injured()->get();
 *
 * // Chain conditions for complex queries
 * $activeReferees = Referee::query()
 *     ->available()
 *     ->whereHas('matches', function ($query) {
 *         $query->where('created_at', '>', now()->subMonths(6));
 *     })
 *     ->get();
 * ```
 */
class RefereeBuilder extends IndividualBuilder
{
    use HasNameSearch;
}
