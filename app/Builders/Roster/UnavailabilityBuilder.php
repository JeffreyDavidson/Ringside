<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Models\Roster\Availability\Unavailability;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Unavailability
 *
 * @extends Builder<TModel>
 */
class UnavailabilityBuilder extends Builder
{
    public function active(): static
    {
        return $this->whereNull('cancelled_at');
    }

    public function covering(string $date): static
    {
        return $this->active()->where('starts_on', '<=', $date)->where('ends_on', '>=', $date);
    }
}
