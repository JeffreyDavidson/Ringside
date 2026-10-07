<?php

declare(strict_types=1);

namespace App\Builders\Events;

use App\Builders\Concerns\FiltersByName;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Venue
 *
 * @extends Builder<TModel>
 */
class VenueBuilder extends Builder
{
    use FiltersByName;

    public function alphabetical(): static
    {
        $this->orderBy('name');

        return $this;
    }

    /** Venues that at least one event uses; venues are shared across promotions, so this keeps lists short. */
    public function hostingEvents(): static
    {
        return $this->whereIn('id', Event::query()->select('venue_id'));
    }
}
