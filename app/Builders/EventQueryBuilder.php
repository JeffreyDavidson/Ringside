<?php

namespace App\Builders;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;

class EventQueryBuilder extends Builder
{
    /**
     * Scope a query to include scheduled events.
     *
     * @return $this
     */
    public function scheduled()
    {
        return $this->where('status', EventStatus::scheduled())->whereNotNull('date');
    }

    /**
     * Scope a query to include unscheduled events.
     *
     * @return $this
     */
    public function unscheduled()
    {
        return $this->where('status', EventStatus::unscheduled())->whereNull('date');
    }

    /**
     * Scope a query to include past events.
     *
     * @return $this
     */
    public function past()
    {
        return $this->where(function () {
            $this->where('status', EventStatus::past())->where('date', '<', now()->toDateString());
        });
    }
}
