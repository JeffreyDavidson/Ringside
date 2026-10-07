<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Models\Roster\Wrestlers\Wrestler;

/**
 * @template TModel of Wrestler
 *
 * @extends IndividualBuilder<TModel>
 */
class WrestlerBuilder extends IndividualBuilder
{
    /**
     * Restrict to wrestlers that can be added to a stable: bookable (employed, not retired, suspended or injured)
     * and not a current member of another stable.
     */
    public function joinableToStable(): static
    {
        return $this->bookable()->whereDoesntHave('currentStable');
    }
}
