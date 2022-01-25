<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

class EventMatchCompetitorsCollection extends Collection
{
    /**
     * Undocumented function.
     *
     * @return \Illuminate\Support\Collection
     */
    public function groupedBySide()
    {
        return $this->groupBy('side_number');
    }

    /**
     * Undocumented function
     *
     * @return \Illuminate\Support\Collection
     */
    public function groupedByType()
    {
        return $this->keyBy('event_match_competitor_type');
    }

    /**
     * Undocumented function
     *
     * @param  mixed $type
     * @return \Illuminate\Support\Collection
     */
    public function filterByType($type)
    {
        return $this->filter(function ($value, $key) use ($type) {
            if ($key === $type) {
                return true;
            }
        });
    }
}
