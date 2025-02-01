<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\Referee;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Referee
 *
 * @extends Builder<TModel>
 */
class RefereeBuilder extends Builder
{
    /**
     * Scope a query to include bookable referees.
     */
    public function bookable(): static
    {
        $this->whereHas('currentEmployment')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentInjury');

        return $this;
    }
}
