<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class TagTeamBuilder extends Builder
{
    use Concerns\HasEmployments;
    use Concerns\HasRetirements;
    use Concerns\HasSuspensions;

    /**
     * Scope a query to only include bookable tag teams.
     */
    public function bookable(): self
    {
        return $this->where('status', 'bookable');
    }

    /**
     * Scope a query to only include bookable tag teams.
     */
    public function unbookable(): self
    {
        return $this->where('status', 'unbookable');
    }
}
