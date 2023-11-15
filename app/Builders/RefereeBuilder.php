<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class RefereeBuilder extends Builder
{
    use Concerns\HasEmployments;
    use Concerns\HasInjuries;
    use Concerns\HasRetirements;
    use Concerns\HasSuspensions;

    /**
     * Scope a query to include bookable referees.
     */
    public function bookable(): self
    {
        $this->whereHas('currentEmployment')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentInjury');

        return $this;
    }
}
