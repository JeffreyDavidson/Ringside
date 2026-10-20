<?php

declare(strict_types=1);

namespace App\Queries\Roster;

use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;

/** A person's cancelled or ended unavailability entries as of the given day, latest first. */
class UnavailabilityHistoryQuery
{
    /**
     * @param  string  $day  the promotion's current day as Y-m-d
     * @return Builder<Unavailability>
     */
    public function get(Wrestler|Referee $person, string $day): Builder
    {
        return Unavailability::query()
            ->whereMorphedTo('person', $person)
            ->where('promotion_id', $person->promotion_id)
            ->where(fn (Builder $query): Builder => $query->whereNotNull('cancelled_at')->orWhere('ends_on', '<', $day))
            ->orderByDesc('starts_on')
            ->orderByDesc('id');
    }
}
