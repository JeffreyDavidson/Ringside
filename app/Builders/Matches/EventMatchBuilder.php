<?php

declare(strict_types=1);

namespace App\Builders\Matches;

use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @template TModel of EventMatch
 *
 * @extends Builder<TModel>
 */
class EventMatchBuilder extends Builder
{
    public function forPastEvents(): static
    {
        $this->withWhereHas('event', function (Builder|Relation $query): void {
            $query->where('date', '<', now());
        });

        return $this;
    }
}
