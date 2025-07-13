<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Models\Wrestlers\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

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
 * @extends SingleRosterMemberBuilder<TModel>
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
class WrestlerBuilder extends SingleRosterMemberBuilder
{
    // Wrestlers inherit all availability, employment, injury, retirement, and suspension scopes from base class

    /**
     * Scope a query to include wrestlers available on a specific date.
     *
     * Filters wrestlers that are available for booking on the given date,
     * considering both their general availability status and any existing
     * match bookings on that date.
     *
     * @param  Carbon  $date  The date to check availability for
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get wrestlers available for a specific event date
     * $availableWrestlers = Wrestler::query()
     *     ->availableOn(Carbon::parse('2024-12-31'))
     *     ->get();
     *
     * // Find wrestlers available for next week's shows
     * $availableWrestlers = Wrestler::query()
     *     ->availableOn(now()->addWeek())
     *     ->get();
     * ```
     */
    public function availableOn(Carbon $date): static
    {
        return $this->available()
            ->notBookedOn($date);
    }

    /**
     * Scope a query to include bookable wrestlers.
     *
     * Filters wrestlers that are currently available for booking in matches.
     * Bookable wrestlers must be available (employed, not injured, not suspended,
     * not retired) and ready for competition assignment.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get all bookable wrestlers
     * $bookableWrestlers = Wrestler::query()->bookable()->get();
     *
     * // Find wrestlers ready for title matches
     * $titleReadyWrestlers = Wrestler::query()
     *     ->bookable()
     *     ->whereDoesntHave('currentChampionships')
     *     ->get();
     * ```
     */
    public function bookable(): static
    {
        return $this->available();
    }

    /**
     * Scope a query to exclude wrestlers already booked on a specific date.
     *
     * Filters out wrestlers that have existing match bookings on the given
     * date to prevent double-booking conflicts.
     *
     * @param  Carbon  $date  The date to check for existing bookings
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * // Get wrestlers not already booked for New Year's Eve
     * $unbookedWrestlers = Wrestler::query()
     *     ->notBookedOn(Carbon::parse('2024-12-31'))
     *     ->get();
     *
     * // Find wrestlers available for emergency booking
     * $emergencyWrestlers = Wrestler::query()
     *     ->available()
     *     ->notBookedOn(now())
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
}
