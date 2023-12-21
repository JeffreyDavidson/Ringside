<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \App\Models\TagTeam
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModelClass>
 */
class TagTeamBuilder extends Builder
{
    use Concerns\HasEmployments;
    use Concerns\HasRetirements;
    use Concerns\HasSuspensions;

    /**
     * Scope a query to include bookable tag teams.
     */
    public function bookable(): static
    {
        $this->where('status', 'bookable');

        return $this;
    }

    /**
     * Scope a query to include bookable tag teams.
     */
    public function unbookable(): static
    {
        $this->where('status', 'unbookable');

        return $this;
    }
}
