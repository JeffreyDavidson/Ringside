<?php

declare(strict_types=1);

namespace App\Builders\Events;

use App\Builders\Concerns\FiltersByName;
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
}
