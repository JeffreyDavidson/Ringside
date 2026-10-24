<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\Events\Event;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Roster\UnavailabilityOnDateQuery;
use Illuminate\Support\Collection;

/** Which wrestlers and referees on an event's card are unavailable on the event's day. */
class EventRosterAvailabilityService
{
    public function __construct(private readonly EventRosterService $roster, private readonly UnavailabilityOnDateQuery $unavailability) {}

    /**
     * The wrestlers and referees on the event's open card who are unavailable on the event's day.
     *
     * @return Collection<int, Wrestler|Referee>
     */
    public function get(Event $event): Collection
    {
        if ($this->day($event) === null) {
            return collect();
        }

        return $this->among($event, $this->roster->forEvent($event, excludePastResults: true)['people']);
    }

    /**
     * The given people who are unavailable on the event's day.
     *
     * @param  iterable<int, Wrestler|Referee>  $people
     * @return Collection<int, Wrestler|Referee>
     */
    public function among(Event $event, iterable $people): Collection
    {
        $date = $this->day($event);

        if ($date === null) {
            return collect();
        }

        $people = collect($people);
        $blocked = $this->unavailability->get($event->promotion_id, $date, $people->map(fn (Wrestler|Referee $person): int => $person->id)->values())
            ->map(fn (Unavailability $entry): string => "{$entry->person_type}:{$entry->person_id}")->all();

        return $people->filter(fn (Wrestler|Referee $person): bool => in_array(EventRosterService::key($person), $blocked, true))->values();
    }

    /** The event's day in its promotion's time zone as Y-m-d, or null while the event has no date. */
    private function day(Event $event): ?string
    {
        $event->loadMissing('promotion');

        return $event->local_date?->toDateString();
    }
}
