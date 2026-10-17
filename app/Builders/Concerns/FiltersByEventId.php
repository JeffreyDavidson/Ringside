<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

/** Limits a query on a model with an `event_id` column to the records owned by one event. */
trait FiltersByEventId
{
    public function forEventId(int $eventId): static
    {
        return $this->where('event_id', $eventId);
    }
}
