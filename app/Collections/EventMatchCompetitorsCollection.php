<?php

declare(strict_types=1);

namespace App\Collections;

use Illuminate\Support\Collection;
use App\Models\EventMatchCompetitor;
use Illuminate\Database\Eloquent\Collection;

class EventMatchCompetitorsCollection extends Collection
{
    /**
     * Undocumented function.
     *
     * @return \Illuminate\Support\Collection
     */
    public function groupedBySide(): Collection
    {
        return EventMatchCompetitor::findMany($this->modelKeys())->groupBy('side_number');
    }

    /**
     * Undocumented function.
     *
     * @return \Illuminate\Support\Collection
     */
    public function groupedByCompetitorType(): Collection
    {
        return $this->groupBy(function ($group) {
            return match ($group->competitor_type) {
                \App\Models\Wrestler::class => 'wrestlers',
                \App\Models\TagTeam::class => 'tag_teams'
            };
        });
    }
}
