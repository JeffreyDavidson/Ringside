<?php

declare(strict_types=1);

namespace App\Builders\Matches;

use App\Models\Matches\MatchStipulation;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of MatchStipulation
 *
 * @extends Builder<TModel>
 */
class MatchStipulationBuilder extends Builder
{
    /** Stipulations that can still be chosen when booking a match. */
    public function active(): static
    {
        return $this->where('is_active', true);
    }

    public function alphabetical(): static
    {
        return $this->orderBy('name');
    }
}
