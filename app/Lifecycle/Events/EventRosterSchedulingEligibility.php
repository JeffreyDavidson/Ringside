<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Lifecycle\Roster\Booking\MatchRosterLock;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Events\EventRosterService;
use Illuminate\Support\Carbon;

/** An event can only move to a date on which everyone already booked on its card is available. */
final readonly class EventRosterSchedulingEligibility
{
    public function __construct(
        private MatchRosterLock $rosterLock,
        private RosterDateAvailability $availability,
        private EventRosterService $roster,
    ) {}

    /** The owning event/date-slot locks must already be held when $lock is true. */
    public function ensureAvailableAt(Event $event, ?Carbon $date, bool $lock = false): void
    {
        if (! $date instanceof Carbon) {
            return;
        }

        if ($lock) {
            $entries = $this->roster->forEvent($event)['entries'];
            $this->rosterLock->lock(
                $entries->filter(fn (Wrestler|Referee|TagTeam $person): bool => $person instanceof Referee)->values(),
                $entries->filter(fn (Wrestler|Referee|TagTeam $person): bool => $person instanceof Wrestler)->values(),
                $entries->filter(fn (Wrestler|Referee|TagTeam $person): bool => $person instanceof TagTeam)->values(),
            );
        }
        $proposed = clone $event;
        $proposed->date = $date;

        foreach ($this->roster->forEvent($event)['people'] as $person) {
            $this->availability->ensureForEvent($person, $proposed);
        }
    }
}
