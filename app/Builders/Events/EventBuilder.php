<?php

declare(strict_types=1);

namespace App\Builders\Events;

use App\Models\Events\Event;
use Illuminate\Database\Eloquent\Builder;

/**
 * Custom query builder for the Event model.
 *
 * Provides query methods for filtering events by their scheduling state.
 *
 * @template TModel of Event
 *
 * @extends Builder<TModel>
 *
 * @example
 * ```php
 * // Get all scheduled events
 * $scheduledEvents = Event::query()->scheduled()->get();
 *
 * // Get recent past events
 * $recentPastEvents = Event::query()
 *     ->past()
 *     ->where('date', '>=', now()->subMonths(3))
 *     ->orderBy('date', 'desc')
 *     ->get();
 * ```
 */
class EventBuilder extends Builder
{
    /**
     * Scope a query to include scheduled events.
     *
     * Filters events that have been officially scheduled with a confirmed date.
     * Scheduled events have a non-null date value and are ready for promotion and ticket sales.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $scheduledEvents = Event::query()->scheduled()->get();
     * ```
     */
    public function scheduled(): static
    {
        $this->whereNotNull('date');

        return $this;
    }

    /**
     * Scope a query to include past events.
     *
     * Filters events that have already occurred based on their date.
     * Past events have a date before today and are used for record keeping and statistics.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $pastEvents = Event::query()->past()->get();
     * ```
     */
    public function past(): static
    {
        $this->where('date', '<', now()->toDateString());

        return $this;
    }

    /**
     * Scope a query to include unscheduled events.
     *
     * Filters events that have been created but don't have a confirmed date yet.
     * Unscheduled events are in planning stages and need date assignment
     * before they can be promoted or tickets sold.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $unscheduledEvents = Event::query()->unscheduled()->get();
     * ```
     */
    public function unscheduled(): static
    {
        $this->whereNull('date');

        return $this;
    }
}
