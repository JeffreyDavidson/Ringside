<?php

declare(strict_types=1);

namespace App\Builders\Events;

use App\Models\Events\Event;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Event
 *
 * @extends Builder<TModel>
 */
class EventBuilder extends Builder
{
    public function latestDatedFirst(): static
    {
        $this->orderByRaw('CASE WHEN date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('date');

        return $this;
    }

    public function scheduled(): static
    {
        $this->where('date', '>=', now());

        return $this;
    }

    public function past(): static
    {
        $this->where('date', '<', now());

        return $this;
    }

    public function unscheduled(): static
    {
        $this->whereNull('date');

        return $this;
    }
}
