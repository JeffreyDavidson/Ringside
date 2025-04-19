<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Event
 *
 * @extends Builder<TModel>
 */
class EventBuilder extends Builder
{
    /**
     * Scope a query to include scheduled events.
     */
    public function scheduled(): static
    {
        $this->where('status', EventStatus::Scheduled)
            ->whereNotNull('date');

        return $this;
    }

    /**
     * Scope a query to include past events.
     */
    public function past(): static
    {
        $this->where('status', EventStatus::Past)
            ->where('date', '<', now()->toDateString());

        return $this;
    }

    /**
     * Scope a query to include unscheduled events.
     */
    public function unscheduled(): static
    {
        $this->whereNull('date');

        return $this;
    }
}
