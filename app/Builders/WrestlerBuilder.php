<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class WrestlerBuilder extends Builder
{
    use Concerns\HasEmployments;
    use Concerns\HasInjuries;
    use Concerns\HasRetirements;
    use Concerns\HasSuspensions;

    /**
     * Scope a query to only include bookable wrestlers.
     */
    public function bookable(): self
    {
        return $this->whereHas('currentEmployment')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentInjury');
    }
}
