<?php

declare(strict_types=1);

namespace App\Queries\Roster;

use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;

/** A person's active unavailability entries that have not ended on the given day, earliest first. */
class CurrentUnavailabilitiesQuery
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
            ->active()
            ->where('ends_on', '>=', $day)
            ->orderBy('starts_on')
            ->orderBy('id');
    }
}
