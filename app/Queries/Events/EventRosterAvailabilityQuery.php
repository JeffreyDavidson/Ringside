<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Events\Event;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;

/** The wrestlers and referees on an event's open card who are unavailable on the event's day. */
class EventRosterAvailabilityQuery
{
    public function __construct(private readonly EventRosterQuery $roster) {}

    /** @return Collection<int, Wrestler|Referee> */
    public function get(Event $event): Collection
    {
        $event->loadMissing('promotion');
        $date = $event->local_date?->toDateString();
        if ($date === null) {
            return collect();
        }
        $people = $this->roster->get($event, excludePastResults: true)['people'];
        $blocked = Unavailability::query()->where('promotion_id', $event->promotion_id)->covering($date)
            ->whereIn('person_id', $people->pluck('id'))->get()
            ->map(fn (Unavailability $entry): string => $entry->person_type.':'.$entry->person_id)->all();

        return $people->filter(fn (Wrestler|Referee $person): bool => in_array(RosterDateAvailability::key($person), $blocked, true))->values();
    }
}
