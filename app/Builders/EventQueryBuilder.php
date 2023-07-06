<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;

class EventQueryBuilder extends Builder
{
    /**
     * Scope a query to include scheduled events.
     */
    public function scheduled(): self
    {
        return $this->where('status', EventStatus::SCHEDULED)->whereNotNull('date');
    }

    /**
     * Scope a query to include unscheduled events.
     */
    public function unscheduled(): self
    {
        return $this->where('status', EventStatus::UNSCHEDULED)->whereNull('date');
    }

    /**
     * Scope a query to include past events.
     */
    public function past(): self
    {
        return $this->where('status', EventStatus::PAST)->where('date', '<', now()->toDateString());
    }
}
