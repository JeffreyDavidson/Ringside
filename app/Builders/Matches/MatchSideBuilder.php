<?php

declare(strict_types=1);

namespace App\Builders\Matches;

use App\Models\Matches\MatchSide;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<MatchSide>
 */
class MatchSideBuilder extends Builder
{
    public function orderedByPosition(): static
    {
        $this->orderBy('position');

        return $this;
    }
}
