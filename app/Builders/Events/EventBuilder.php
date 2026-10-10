<?php

declare(strict_types=1);

namespace App\Builders\Events;

use App\Builders\Concerns\FiltersByName;
use App\Builders\Concerns\FiltersByNameInPromotion;
use App\Enums\EventStatus;
use App\Models\Events\Event;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Event
 *
 * @extends Builder<TModel>
 */
class EventBuilder extends Builder
{
    use FiltersByName;
    use FiltersByNameInPromotion;

    public function forVenueId(int $venueId): static
    {
        $this->where('venue_id', $venueId);

        return $this;
    }

    public function whereStatus(EventStatus $status): static
    {
        return match ($status) {
            EventStatus::Past => $this->past(),
            EventStatus::Scheduled => $this->scheduled(),
            EventStatus::Unscheduled => $this->unscheduled(),
        };
    }

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

    /** Events dated within the range, both instants included. */
    public function heldBetween(CarbonInterface $start, CarbonInterface $end): static
    {
        return $this->whereBetween('date', [$start, $end]);
    }
}
