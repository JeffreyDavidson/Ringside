<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \App\Models\Event
 * @extends Builder<\App\Models\Event>
 */
class EventQueryBuilder extends Builder
{
    /**
     * Scope a query to include scheduled events.
     *
     * @return \App\Builders\EventQueryBuilder
     */
    public function scheduled()
    {
        return $this->where('status', EventStatus::SCHEDULED)->whereNotNull('date');
    }

    /**
     * Scope a query to include unscheduled events.
     *
     * @return \App\Builders\EventQueryBuilder
     */
    public function unscheduled()
    {
        return $this->where('status', EventStatus::UNSCHEDULED)->whereNull('date');
    }

    /**
     * Scope a query to include past events.
     *
     * @return \App\Builders\EventQueryBuilder
     */
    public function past()
    {
        return $this->where('status', EventStatus::PAST)->where('date', '<', now()->toDateString());
    }
}
