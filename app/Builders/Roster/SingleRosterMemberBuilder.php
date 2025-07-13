<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\HasAvailabilityScopes;
use App\Builders\Concerns\HasRetirementScopes;
use App\Builders\Contracts\HasAvailability;
use App\Builders\Contracts\HasEmployment;
use App\Builders\Contracts\HasRetirement;
use App\Builders\Contracts\HasSuspension;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base query builder for individual roster member models.
 *
 * This abstract builder provides common query functionality for individual
 * roster members (Wrestlers, Managers, Referees) who share similar status
 * and employment patterns. It consolidates shared traits and methods to
 * reduce code duplication across individual roster member builders.
 *
 * BUSINESS CONTEXT:
 * Individual roster members share many characteristics:
 * - Employment status tracking
 * - Injury capabilities and management
 * - Retirement lifecycle management
 * - Suspension and reinstatement processes
 *
 * DESIGN PATTERN:
 * Template method pattern - Defines common structure with extensible
 * behavior in child classes for entity-specific requirements.
 *
 * @template TModel of Model The individual roster member model type
 *
 * @example
 * ```php
 * // Usage in child builders
 * class WrestlerBuilder extends SingleRosterMemberBuilder
 * {
 *     use HasBookingScopes; // Wrestlers can be booked
 * }
 *
 * class ManagerBuilder extends SingleRosterMemberBuilder
 * {
 *     // Managers don't need booking scopes
 * }
 * ```
 *
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
abstract class SingleRosterMemberBuilder extends Builder implements HasAvailability, HasEmployment, HasRetirement, HasSuspension
{
    use HasAvailabilityScopes;
    use HasRetirementScopes;

    /**
     * Scope a query to include available individual roster members.
     *
     * Filters individual roster members that are currently available to perform
     * their duties. For individual roster members (wrestlers, managers, referees),
     * availability means they are employed (with employment having started),
     * not injured, not suspended, and not retired.
     *
     * This is the primary method for checking roster member readiness and should
     * be used instead of deprecated status-checking methods.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all available wrestlers for match booking
     * $availableWrestlers = Wrestler::query()->available()->get();
     *
     * // Get all available managers for assignment
     * $availableManagers = Manager::query()->available()->get();
     *
     * // Chain with other conditions for active roster count
     * $activeRosterCount = Referee::query()
     *     ->available()
     *     ->whereHas('matches', function ($query) {
     *         $query->where('created_at', '>', now()->subMonth());
     *     })
     *     ->count();
     * ```
     */
    public function available(): static
    {
        $this->whereEmployed()
            ->whereNotInjured()
            ->whereNotSuspended()
            ->whereNotRetired();

        return $this;
    }

    /**
     * Scope a query to include unavailable individual roster members.
     *
     * Filters individual roster members that cannot currently perform their duties
     * due to employment, injury, suspension, or retirement status.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all unavailable wrestlers
     * $unavailableWrestlers = Wrestler::query()->unavailable()->get();
     *
     * // Find managers who need attention
     * $needsAttention = Manager::query()
     *     ->unavailable()
     *     ->with(['currentInjury', 'currentSuspension'])
     *     ->get();
     * ```
     */
    public function unavailable(): static
    {
        $this->where(function (Builder $query) {
            $query->whereDoesntHave('currentEmployment')
                ->orWhereHas('currentInjury')
                ->orWhereHas('currentSuspension')
                ->orWhereHas('currentRetirement');
        });

        return $this;
    }

    /**
     * Scope a query to include unemployed individual roster members.
     *
     * Filters individual roster members that are currently unemployed and not
     * under contract. These entities do not have a current employment relationship.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all unemployed wrestlers
     * $unemployedWrestlers = Wrestler::query()->unemployed()->get();
     *
     * // Find managers available for hiring
     * $availableManagers = Manager::query()
     *     ->unemployed()
     *     ->whereDoesntHave('currentRetirement')
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
     * Scope a query to include employed individual roster members.
     *
     * Filters individual roster members that are currently employed and under
     * contract. Uses the currentEmployment relationship to check for active
     * employment (where ended_at is null and started_at <= now).
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all employed wrestlers
     * $employedWrestlers = Wrestler::query()->employed()->get();
     *
     * // Find employed referees for upcoming matches
     * $availableReferees = Referee::query()
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
     * Scope a query to include released individual roster members.
     *
     * Filters individual roster members that have been released from their
     * contracts. Released entities have completed employment records but
     * no current employment.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all released wrestlers
     * $releasedWrestlers = Wrestler::query()->released()->get();
     *
     * // Find recently released managers
     * $recentlyReleased = Manager::query()
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
     * Scope a query to include individual roster members with future employment.
     *
     * Filters individual roster members that have been signed but their
     * employment hasn't started yet (started_at > now and ended_at is null).
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all wrestlers with future contracts
     * $futureEmployed = Wrestler::query()->futureEmployed()->get();
     *
     * // Find referees starting soon
     * $startingSoon = Referee::query()
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
     * Scope a query to include injured individual roster members.
     *
     * Filters individual roster members that are currently injured and cannot
     * perform their duties until they are medically cleared. Uses the currentInjury
     * relationship to check for active injuries (where ended_at is null).
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all injured wrestlers
     * $injuredWrestlers = Wrestler::query()->injured()->get();
     *
     * // Find managers who need medical clearance
     * $needsClearance = Manager::query()
     *     ->injured()
     *     ->with('currentInjury')
     *     ->get();
     *
     * // Check if any referees are injured
     * $hasInjuredReferees = Referee::query()->injured()->exists();
     * ```
     */
    public function injured(): static
    {
        $this->whereHas('currentInjury');

        return $this;
    }

    /**
     * Scope a query to include suspended entities.
     *
     * Filters entities that are currently suspended and cannot participate
     * in events until their suspension is lifted. Uses the currentSuspension
     * relationship to check for active suspensions (where ended_at is null).
     *
     * @return static The builder instance for method chaining
     */
    public function suspended(): static
    {
        $this->whereHas('currentSuspension');

        return $this;
    }

    /**
     * Scope a query to include entities available on a specific date.
     *
     * Filters individual roster members that are available for general duties
     * on the given date. Note: Match booking availability should be handled
     * in specific builders (WrestlerBuilder, TagTeamBuilder) as only wrestlers
     * and tag teams can be booked for matches.
     *
     * For managers and referees, the date parameter is not used since they
     * are not booked for matches, but the signature is kept for consistency.
     *
     * @param  Carbon  $date  The date to check availability for (unused for non-bookable entities)
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get managers available for assignment on a specific date
     * $availableManagers = Manager::query()
     *     ->availableOn(now()->addWeek())
     *     ->get();
     *
     * // Get referees available for general duties
     * $availableReferees = Referee::query()
     *     ->availableOn(Carbon::parse('2024-12-31'))
     *     ->get();
     * ```
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function availableOn(Carbon $date): static
    {
        // Note: $date parameter is intentionally unused for managers/referees
        // as they are not booked for matches. Method signature kept for consistency.
        return $this->available();
    }
}
