<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Builders\Matches\EventMatchBuilder;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Support\Collection;

/** Everyone on an event's card, as booked (wrestlers, tag teams, referees) and as individuals (tag teams replaced by their current members). */
class EventRosterQuery
{
    /** @return array{entries: Collection<int, Wrestler|Referee|TagTeam>, people: Collection<int, Wrestler|Referee>} */
    public function get(Event $event, bool $excludePastResults = false): array
    {
        $entries = $this->entries($event, $excludePastResults);
        $teams = new ModelCollection($entries->filter(fn (Wrestler|Referee|TagTeam $person): bool => $person instanceof TagTeam)->all());
        $teams->load('currentWrestlers');

        return [
            'entries' => $entries,
            'people' => $entries->reject(fn (Wrestler|Referee|TagTeam $person): bool => $person instanceof TagTeam)
                ->merge($teams->flatMap(fn (TagTeam $team): array => $team->currentWrestlers->all()))
                ->unique(RosterDateAvailability::key(...))->values(),
        ];
    }

    /** @return Collection<int, Wrestler|Referee|TagTeam> */
    private function entries(Event $event, bool $excludePastResults): Collection
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
}
