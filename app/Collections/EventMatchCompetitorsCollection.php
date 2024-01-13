<?php

declare(strict_types=1);

namespace App\Collections;

use App\Models\EventMatchCompetitor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TKey of array-key
 * @template TModel of Model
 *
 * @extends \Illuminate\Database\Eloquent\Collection<TKey, TModel>
 */
class EventMatchCompetitorsCollection extends Collection
{
    /**
     * Get all competitors for a match grouped by side.
     *
     * @return Collection<array-key, Collection<(int|string), EventMatchCompetitor>>
     */
    public function propertlyFormattedCompetitors(): Collection
    {
        $this->groupBy('side_number');

        return $this;
    }
}
