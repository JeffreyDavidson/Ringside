<?php

declare(strict_types=1);

namespace App\Queries\Roster;

use App\Models\Roster\Availability\Unavailability;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/** The unavailability entries of a promotion that cover a day, for the given people. */
class UnavailabilityOnDateQuery
{
    /**
     * @param  string  $date  the day as Y-m-d
     * @param  BaseCollection<int, int>  $personIds  wrestler and referee ids; the caller matches each entry to its person type
     * @return Collection<int, Unavailability>
     */
    public function get(?int $promotionId, string $date, BaseCollection $personIds): Collection
    {
        return Unavailability::query()->where('promotion_id', $promotionId)->covering($date)->whereIn('person_id', $personIds)->get();
    }
}
