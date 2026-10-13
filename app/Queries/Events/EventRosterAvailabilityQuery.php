<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Builders\Matches\EventMatchBuilder;
use App\Lifecycle\Roster\Booking\MatchRosterLock;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EventRosterAvailabilityQuery
{
    public function __construct(private readonly MatchRosterLock $rosterLock, private readonly RosterDateAvailability $availability) {}

    /** @return Collection<int, Wrestler|Referee|TagTeam> */
    private function entries(Event $event, bool $excludePastResults = false): Collection
    {
        if (! $event->exists) {
            return collect();
        }
        // A restored event is still soft-deleted here. The caller owns this exact event; never search another promotion.
        $matches = EventMatch::query()->withoutGlobalScope(PromotionContextScope::class)->forEventId($event->id)
            ->when($excludePastResults && $event->date?->isPast(), fn (EventMatchBuilder $query): EventMatchBuilder => $query->whereNull('match_finish'))
            ->withDisplayRelations()->get();

        return $matches->toBase()->flatMap(fn (EventMatch $match): Collection => $match->competitors->toBase()
            ->map(fn (MatchCompetitor $entry): Wrestler|TagTeam => $entry->competitor)->merge($match->referees->toBase()))
            ->filter(fn (Wrestler|Referee|TagTeam $person): bool => $person->promotion_id === $event->promotion_id)
            ->unique(RosterDateAvailability::key(...))->values();
    }

    /** @return Collection<int, Wrestler|Referee> */
    public function people(Event $event, bool $excludePastResults = false): Collection
    {
        $entries = $this->entries($event, $excludePastResults);
        $teams = new ModelCollection($entries->filter(fn (Wrestler|Referee|TagTeam $person): bool => $person instanceof TagTeam)->all());
        $teams->load('currentWrestlers');

        return $entries->reject(fn (Wrestler|Referee|TagTeam $person): bool => $person instanceof TagTeam)
            ->merge($teams->flatMap(fn (TagTeam $team): array => $team->currentWrestlers->all()))
            ->unique(RosterDateAvailability::key(...))->values();
    }

    /** @return Collection<int, Wrestler|Referee> */
    public function unavailable(Event $event): Collection
    {
        $event->loadMissing('promotion');
        $date = $event->local_date?->toDateString();
        if ($date === null) {
            return collect();
        }
        $people = $this->people($event, excludePastResults: true);
        $blocked = Unavailability::query()->where('promotion_id', $event->promotion_id)->covering($date)
            ->whereIn('person_id', $people->pluck('id'))->get()
            ->map(fn (Unavailability $entry): string => $entry->person_type.':'.$entry->person_id)->all();

        return $people->filter(fn (Wrestler|Referee $person): bool => in_array(RosterDateAvailability::key($person), $blocked, true))->values();
    }

    /** The owning event/date-slot locks must already be held when $lock is true. */
    public function ensureAt(Event $event, ?Carbon $date, bool $lock = false): void
    {
        if (! $date instanceof Carbon) {
            return;
        }
        if ($lock) {
            $entries = $this->entries($event);
            $this->rosterLock->lock(
                $entries->filter(fn (Wrestler|Referee|TagTeam $person): bool => $person instanceof Referee)->values(),
                $entries->filter(fn (Wrestler|Referee|TagTeam $person): bool => $person instanceof Wrestler)->values(),
                $entries->filter(fn (Wrestler|Referee|TagTeam $person): bool => $person instanceof TagTeam)->values(),
            );
        }
        $proposed = clone $event;
        $proposed->date = $date;
        foreach ($this->people($event) as $person) {
            $this->availability->ensureForEvent($person, $proposed);
        }
    }
}
