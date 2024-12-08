<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \App\Models\Referee
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModel>
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
