<?php

declare(strict_types=1);

namespace App\Builders\Events;

use App\Models\Events\Venue;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Venue
 *
 * @extends Builder<TModel>
 */
class VenueBuilder extends Builder
{
    public function alphabetical(): static
    {
        $this->orderBy('name');

        return $this;
    }
}
