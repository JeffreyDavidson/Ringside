<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Queries\Events\PublicEventMatchesQuery;
use App\Queries\Events\PublicEventPeopleQuery;
use Illuminate\Support\Collection;

/**
 * What the public page may show about an event: its title, promotion, date, venue and match rows, with the
 * fingerprints that tell a draft or a released snapshot the event has moved on.
 *
 * @phpstan-type MatchRow array{id: int, label: string, type: string, stipulation: ?string, titles: string, finish: ?string, winner: ?string}
 * @phpstan-type Source array{fingerprint: string, schedule: string, title: string, promotion: string, date: ?string, timezone: string, venue: ?string, address: ?string, matches: list<MatchRow>}
 */
class PublicEventSourceService
{
    public function __construct(
        private readonly PublicEventMatchesQuery $matches,
        private readonly PublicEventPeopleQuery $people,
        private readonly PublicEventCardService $cards,
    ) {}

    /**
     * Public source reads explicitly constrain ownership, independently of a visitor's authenticated context.
     *
     * @return Source
     */
    public function get(Event $event): array
    {
        $event->load(['promotion', 'venue']);
        $matches = $this->matches->get($event);
        $assignments = $matches->flatMap(fn (EventMatch $match) => $match->sides->flatMap(fn (MatchSide $side) => $side->competitors));
        $people = $this->names($event, Wrestler::class, $assignments->where('competitor_type', 'wrestler'))
            ->merge($this->names($event, TagTeam::class, $assignments->where('competitor_type', 'tag_team')));
        $rows = [];
        foreach ($matches as $match) {
            $sides = $match->sides->mapWithKeys(fn (MatchSide $side): array => [$side->id => $side->competitors->sortBy('id')
                ->map(fn (MatchCompetitor $assignment): string => $people->get($assignment->competitor_type.':'.$assignment->competitor_id, ''))->join(' & ')]);
            $complete = $match->sides->count() >= 2 && ! $match->sides->contains(fn (MatchSide $side): bool => $side->competitors->isEmpty()
                || $side->competitors->contains(fn (MatchCompetitor $assignment): bool => ! $people->has($assignment->competitor_type.':'.$assignment->competitor_id)));
            $winner = null;
            if ($match->match_finish?->requiresWinningSide()) {
                $winner = $match->winning_side_id === null ? '' : $sides->get($match->winning_side_id, '');
            }
            $rows[] = ['id' => $match->id, 'label' => $complete ? $sides->join(' vs ') : '', 'type' => $match->match_type->label(),
                'stipulation' => $match->matchStipulation?->name, 'titles' => $match->titles->map(fn (Title $title): string => $title->name)->join(', '),
                'finish' => $match->match_finish?->label(), 'winner' => $winner];
        }
        $zone = $event->venue->timezone ?? $event->promotion->timezone ?? config()->string('app.timezone');
        $data = ['title' => $event->name, 'promotion' => $event->promotion->name ?? '', 'date' => $event->date?->toIso8601String(),
            'timezone' => $zone, 'venue' => $event->venue?->name, 'address' => $event->venue === null ? null : implode(', ', array_filter([
                $event->venue->street_address, $event->venue->city, $event->venue->state, $event->venue->zipcode])), 'matches' => $rows];

        return [...$data, 'fingerprint' => $this->cards->fingerprint($data), 'schedule' => $this->cards->fingerprint([
            $event->date?->toIso8601String(), $event->venue_id, $event->venue?->name, $zone, $data['address']])];
    }

    /**
     * The names of the wrestlers or tag teams on the card, keyed by `morph class:id`.
     *
     * @param  class-string<Wrestler|TagTeam>  $model
     * @param  Collection<int, MatchCompetitor>  $assignments
     * @return Collection<non-falsy-string, string>
     */
    private function names(Event $event, string $model, Collection $assignments): Collection
    {
        if ($assignments->isEmpty()) {
            return new Collection;
        }

        return $this->people->get($model, $event->promotion_id, $assignments->map(fn (MatchCompetitor $assignment): int => $assignment->competitor_id)->values()->all())
            ->mapWithKeys(fn (Wrestler|TagTeam $person): array => [$person->getMorphClass().':'.$person->id => $person->name]);
    }
}
