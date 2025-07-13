<?php

declare(strict_types=1);

namespace App\Builders;

use App\Builders\Concerns\HasAvailabilityScopes;
use App\Builders\Concerns\HasRetirementScopes;
use App\Builders\Contracts\HasAvailability;
use App\Builders\Contracts\HasBooking;
use App\Builders\Contracts\HasEmployment;
use App\Builders\Contracts\HasRetirement;
use App\Builders\Contracts\HasSuspension;
use App\Models\TagTeams\TagTeam;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Custom query builder for the TagTeam model.
 *
 * Provides specialized query methods for filtering tag teams by their employment status,
 * including available, unavailable, retired, unemployed, suspended, and released tag teams.
 * This builder enables easy filtering of tag teams based on their current availability
 * and employment conditions for match booking.
 *
 * @template TModel of TagTeam
 *
 * @extends Builder<TModel>
 *
 * @example
 * ```php
 * // Get all available tag teams
 * $availableTeams = TagTeam::query()->available()->get();
 *
 * // Get retired tag teams for historical data
 * $retiredTeams = TagTeam::query()->retired()->get();
 *
 * // Chain conditions for complex queries
 * $activeTeams = TagTeam::query()
 *     ->available()
 *     ->whereHas('wrestlers', function ($query) {
 *         $query->where('status', 'available');
 *     })
 *     ->get();
 * ```
 *
 * @template TModel of \App\Models\TagTeams\TagTeam
 *
 * @extends Builder<TModel>
 */
class TagTeamBuilder extends Builder implements HasAvailability, HasBooking, HasEmployment, HasRetirement, HasSuspension
{
    use HasAvailabilityScopes;
    use HasRetirementScopes;

    /**
     * Scope a query to include available tag teams.
     *
     * Filters tag teams that are currently available for booking. For tag teams,
     * availability means they are employed, not suspended, and not retired.
     * Wrestler availability is checked separately through the bookable() scope
     * or specific match booking logic to maintain clear separation of concerns.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all available tag teams (team-level status only)
     * $availableTeams = TagTeam::query()->available()->get();
     *
     * // For match booking, use additional wrestler checks
     * $bookableTeams = TagTeam::query()
     *     ->available()
     *     ->whereHas('currentWrestlers', function ($query) {
     *         $query->available();
     *     })
     *     ->get();
     * ```
     */
    public function available(): static
    {
        $this->whereEmployed()
            ->whereNotSuspended()
            ->whereNotRetired();

        return $this;
    }

    /**
     * Scope a query to include unavailable tag teams.
     *
     * Filters tag teams that cannot currently be considered for booking at the
     * team level. This includes teams that are unemployed, suspended, or retired.
     * Individual wrestler availability is handled separately in match booking logic.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all unavailable tag teams (team-level status only)
     * $unavailableTeams = TagTeam::query()->unavailable()->get();
     *
     * // Find teams that need attention
     * $needsAttention = TagTeam::query()
     *     ->unavailable()
     *     ->with(['currentSuspension', 'currentRetirement'])
     *     ->get();
     * ```
     */
    public function unavailable(): static
    {
        $this->whereBasicUnavailabilityConditions();

        return $this;
    }

    /**
     * Scope a query to include unemployed tag teams.
     *
     * Filters tag teams that do not have current employment contracts.
     * This focuses on the tag team entity's employment status rather than
     * individual wrestler employment status, preventing logical contradictions.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all unemployed tag teams
     * $unemployedTeams = TagTeam::query()->unemployed()->get();
     *
     * // Find teams that need contracts
     * $needsContracts = TagTeam::query()
     *     ->unemployed()
     *     ->with(['currentWrestlers'])
     *     ->get();
     * ```
     */
    public function unemployed(): static
    {
        $this->whereDoesntHave('currentEmployment')
            ->whereDoesntHave('previousEmployments')
            ->whereDoesntHave('futureEmployment');

        return $this;
    }

    /**
     * Scope a query to include employed tag teams.
     *
     * Filters tag teams that have active employment contracts.
     * This focuses on the tag team entity's employment status for
     * consistency with the unemployed() method logic.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all employed tag teams
     * $employedTeams = TagTeam::query()->employed()->get();
     *
     * // Get employed teams ready for matches
     * $readyTeams = TagTeam::query()
     *     ->employed()
     *     ->available()
     *     ->get();
     * ```
     */
    public function employed(): static
    {
        $this->whereHas('currentEmployment', function (Builder $query) {
            $query->where('started_at', '<=', now());
        });

        return $this;
    }

    /**
     * Scope a query to include released tag teams.
     *
     * Filters tag teams that have been released from their contracts.
     * This includes teams that have previous employment but no current
     * or future employment, regardless of wrestler employment status.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all released tag teams
     * $releasedTeams = TagTeam::query()->released()->get();
     *
     * // Find recently released teams
     * $recentlyReleased = TagTeam::query()
     *     ->released()
     *     ->whereHas('previousEmployments', function ($query) {
     *         $query->where('ended_at', '>', now()->subMonths(6));
     *     })
     *     ->get();
     * ```
     */
    public function released(): static
    {
        $this->whereHas('previousEmployments')
            ->whereDoesntHave('currentEmployment')
            ->whereDoesntHave('futureEmployment')
            ->whereDoesntHave('currentRetirement');

        return $this;
    }

    /**
     * Scope a query to include tag teams with future employment.
     *
     * Filters tag teams that have been signed but their employment hasn't
     * started yet. This checks the team's future employment regardless
     * of wrestler employment status.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all teams with future contracts
     * $futureEmployed = TagTeam::query()->futureEmployed()->get();
     *
     * // Find teams starting soon
     * $startingSoon = TagTeam::query()
     *     ->futureEmployed()
     *     ->whereHas('futureEmployment', function ($query) {
     *         $query->where('started_at', '<=', now()->addWeeks(2));
     *     })
     *     ->get();
     * ```
     */
    public function futureEmployed(): static
    {
        $this->whereHas('futureEmployment');

        return $this;
    }

    /**
     * Scope a query to include suspended tag teams.
     *
     * Filters tag teams that are currently suspended and cannot participate
     * in events until their suspension is lifted. For tag teams, suspension
     * is synchronized with wrestler suspensions via business actions.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all suspended tag teams
     * $suspendedTeams = TagTeam::query()->suspended()->get();
     *
     * // Find teams that need reinstatement
     * $needsReinstatement = TagTeam::query()
     *     ->suspended()
     *     ->with('currentSuspension')
     *     ->get();
     * ```
     */
    public function suspended(): static
    {
        $this->whereHas('currentSuspension');

        return $this;
    }

    /**
     * Scope a query to include tag teams with available wrestlers.
     *
     * Filters tag teams where all current wrestler partners are available
     * (employed, not injured, not suspended, not retired). This ensures
     * the team has a complete roster ready for competition.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get tag teams with all wrestlers available
     * $teamsWithAvailableWrestlers = TagTeam::query()
     *     ->withAvailableWrestlers()
     *     ->get();
     *
     * // Find teams ready for immediate booking
     * $immediatelyBookable = TagTeam::query()
     *     ->available()
     *     ->withAvailableWrestlers()
     *     ->get();
     * ```
     */
    public function withAvailableWrestlers(): static
    {
        $this->whereHas('currentWrestlers', function (Builder $query) {
            $query->whereEmployed()
                ->whereNotInjured()
                ->whereNotSuspended()
                ->whereNotRetired();
        });

        return $this;
    }

    /**
     * Scope a query to include tag teams with minimum wrestler count.
     *
     * Filters tag teams that have at least the specified number of current
     * wrestler partners. Defaults to 2 wrestlers (standard tag team size).
     *
     * @param  int  $count  Minimum number of wrestlers required
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get tag teams with at least 2 wrestlers
     * $standardTeams = TagTeam::query()->withMinimumWrestlers()->get();
     *
     * // Get larger tag teams with 3+ wrestlers
     * $largerTeams = TagTeam::query()->withMinimumWrestlers(3)->get();
     * ```
     */
    public function withMinimumWrestlers(int $count = 2): static
    {
        $this->has('currentWrestlers', '>=', $count);

        return $this;
    }

    /**
     * Scope a query to include tag teams ready for booking.
     *
     * Filters tag teams that are completely ready for match booking.
     * This combines team-level availability with wrestler-level availability
     * to ensure the entire unit is ready for competition.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all teams ready for immediate booking
     * $readyTeams = TagTeam::query()->readyForBooking()->get();
     *
     * // Find teams ready for title matches
     * $titleReadyTeams = TagTeam::query()
     *     ->readyForBooking()
     *     ->whereDoesntHave('currentChampionships')
     *     ->get();
     * ```
     */
    public function readyForBooking(): static
    {
        return $this->available()
            ->withMinimumWrestlers();
    }

    /**
     * Scope a query to include bookable tag teams.
     *
     * Filters tag teams that are currently available for booking in matches.
     * Bookable tag teams must be ready for booking (team available, wrestlers
     * available, minimum wrestler count met).
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all bookable tag teams
     * $bookableTeams = TagTeam::query()->bookable()->get();
     *
     * // Find bookable teams for title matches
     * $titleBookableTeams = TagTeam::query()
     *     ->bookable()
     *     ->whereDoesntHave('currentChampionships')
     *     ->get();
     * ```
     */
    public function bookable(): static
    {
        return $this->readyForBooking();
    }

    /**
     * Scope a query to include tag teams available on a specific date.
     *
     * Filters tag teams that are available for booking on the given date,
     * considering both team availability and existing match bookings.
     *
     * @param  Carbon  $date  The date to check availability for
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get tag teams available for a specific event date
     * $availableTeams = TagTeam::query()
     *     ->availableOn(Carbon::parse('2024-12-31'))
     *     ->get();
     * ```
     */
    public function availableOn(Carbon $date): static
    {
        return $this->bookable()
            ->notBookedOn($date);
    }

    /**
     * Scope a query to exclude tag teams already booked on a specific date.
     *
     * Filters out tag teams that have existing match bookings on the given
     * date to prevent double-booking conflicts.
     *
     * @param  Carbon  $date  The date to check for existing bookings
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get tag teams not already booked for New Year's Eve
     * $unbookedTeams = TagTeam::query()
     *     ->notBookedOn(Carbon::parse('2024-12-31'))
     *     ->get();
     * ```
     */
    public function notBookedOn(Carbon $date): static
    {
        $this->whereDoesntHave('matches', function (Builder $query) use ($date) {
            $query->whereHas('event', function (Builder $eventQuery) use ($date) {
                $eventQuery->whereDate('date', $date->toDateString());
            });
        });

        return $this;
    }

    /**
     * Scope a query to include unbookable tag teams.
     *
     * Alias for unavailable() method. Filters tag teams that cannot currently
     * be booked for matches due to employment, suspension, or retirement status.
     *
     * @return static The builder instance for method chaining
     */
    public function unbookable(): static
    {
        return $this->unavailable();
    }
}
