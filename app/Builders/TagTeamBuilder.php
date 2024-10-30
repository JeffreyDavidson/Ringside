<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * @extends \Illuminate\Database\Eloquent\Builder<\App\Models\TagTeam>
 */
class TagTeamBuilder extends Builder
{
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
