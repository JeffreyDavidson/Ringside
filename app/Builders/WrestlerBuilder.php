<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * @extends \Illuminate\Database\Eloquent\Builder<\App\Models\Wrestler>
 */
class WrestlerBuilder extends Builder
{
    /**
     * Scope a query to include bookable wrestlers.
     */
    public function bookable(): static
    {
        $this->whereHas('currentEmployment')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentInjury');

        return $this;
    }
}
