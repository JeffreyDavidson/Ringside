<?php

declare(strict_types=1);

namespace App\Builders\Roster;

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
 * @extends SingleRosterMemberBuilder<TModel>
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
class RefereeBuilder extends SingleRosterMemberBuilder
{
    // Referees inherit all availability, employment, injury, retirement, and suspension scopes from base class

    /**
     * Scope a query to include bookable referees.
     *
     * Filters referees that are currently available for assignment to officiate matches.
     * Bookable referees must be available (employed, not injured, not suspended,
     * not retired) and ready for match assignment.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all bookable referees
     * $bookableReferees = Referee::query()->bookable()->get();
     *
     * // Find referees ready for championship matches
     * $championshipReferees = Referee::query()
     *     ->bookable()
     *     ->whereHas('matches', function ($query) {
     *         $query->where('type', 'championship');
     *     })
     *     ->get();
     * ```
     */
    public function bookable(): static
    {
        return $this->available();
    }
}
