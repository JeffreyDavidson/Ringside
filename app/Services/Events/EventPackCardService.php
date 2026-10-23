<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Queries\Events\BookableIdsQuery;
use App\Queries\Events\EventPackCardMatchesQuery;
use App\Queries\Events\EventVenueQuery;
use App\Services\Promotions\PromotionTimeZoneService;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Support\Collection;

/**
 * The event's card as the pack prints it: one row per match, one row per person on the card, the warnings for people
 * who cannot be booked or are unavailable on the event's day, and the fingerprint that tells a saved pack the card
 * has moved on.
 *
 * @phpstan-type CardRow array{id: int, number: int, label: string, details: string, referees: string, warning: bool}
 * @phpstan-type RosterRow array{key: string, name: string, role: string, matches: list<int>, warning: bool}
 * @phpstan-type Card array{fingerprint: string, timezone: string, rows: list<CardRow>, roster: list<RosterRow>}
 */
class EventPackCardService
{
    public function __construct(
        private readonly EventPackCardMatchesQuery $matches,
        private readonly BookableIdsQuery $bookable,
        private readonly EventRosterService $roster,
        private readonly EventRosterAvailabilityService $availability,
        private readonly EventVenueQuery $venues,
    ) {}

    /** @return Card */
    public function get(Event $event): array
    {
        // A deleted venue still names the event it hosted, so the fingerprint stays the same when the venue is deleted.
        $event->load('promotion');
        $event->setRelation('venue', $this->venues->get($event));
        $matches = $this->matches->get($event);
        $teams = $matches->flatMap(fn (EventMatch $match): Collection => $match->competitors->tagTeams())->unique('id');
        $wrestlers = $matches->flatMap(fn (EventMatch $match): Collection => $match->competitors->wrestlers())
            ->merge($teams->flatMap(fn (TagTeam $team): ModelCollection => $team->currentWrestlers))->unique('id');
        $referees = $matches->flatMap(fn (EventMatch $match): ModelCollection => $match->referees)->unique('id');
        $available = [
            'wrestler' => $this->bookable->get(Wrestler::class, $this->ids($wrestlers)),
            'tag_team' => $this->bookable->get(TagTeam::class, $this->ids($teams)),
            'referee' => $this->bookable->get(Referee::class, $this->ids($referees)),
        ];
        $calendarUnavailable = $this->calendarUnavailable($event, $matches)->map(EventRosterService::key(...))->all();
        $rows = [];
        $roster = [];

        foreach ($matches as $match) {
            $warning = false;
            $sides = $match->competitors->competitorModelsBySidePosition();

            foreach ($match->competitors as $assignment) {
                $competitor = $assignment->competitor;
                $calendarRelevant = $match->match_finish === null || $event->date >= now();
                $dateBlocked = $calendarRelevant && in_array(EventRosterService::key($competitor), $calendarUnavailable, true);
                $warning = $warning || $dateBlocked || ! in_array($competitor->id, $available[$competitor->getMorphClass()], true);

                if ($competitor instanceof TagTeam) {
                    foreach ($competitor->currentWrestlers as $wrestler) {
                        $blocked = $calendarRelevant && in_array(EventRosterService::key($wrestler), $calendarUnavailable, true);
                        $warning = $warning || $blocked;
                        $this->addPerson($roster, $wrestler, $competitor->name, $match->match_number, $available['wrestler'], $blocked);
                    }

                    continue;
                }
                $this->addPerson($roster, $competitor, __('event-pack.wrestler'), $match->match_number, $available['wrestler'], $dateBlocked);
            }

            foreach ($match->referees as $referee) {
                $dateBlocked = ($match->match_finish === null || $event->date >= now()) && in_array(EventRosterService::key($referee), $calendarUnavailable, true);
                $warning = $warning || $dateBlocked || ! in_array($referee->id, $available['referee'], true);
                $this->addPerson($roster, $referee, __('event-pack.referee'), $match->match_number, $available['referee'], $dateBlocked);
            }
            $rows[] = [
                'id' => $match->id, 'number' => $match->match_number,
                'label' => $sides->map(fn (Collection $side): string => $side->map(fn (Wrestler|TagTeam $person): string => $person->name)->join(' & '))->join(' vs '),
                'details' => implode(' · ', array_filter([$match->match_type->label(), $match->matchStipulation?->name, implode(', ', $match->titles->map(fn (Title $title): string => $title->name)->all())])),
                'referees' => implode(', ', $match->referees->map(fn (Referee $referee): string => $referee->full_name)->all()), 'warning' => $warning,
            ];
        }
        $zone = PromotionTimeZoneService::zoneOf($event->promotion);
        $roster = array_values($roster);

        return [
            'fingerprint' => hash('sha256', serialize([$event->name, $event->date?->toIso8601String(), $event->venue_id, $event->venue?->name, $zone, $rows, $roster])),
            'timezone' => $zone, 'rows' => $rows, 'roster' => $roster,
        ];
    }

    /**
     * @template TPerson of Wrestler|TagTeam|Referee
     *
     * @param  Collection<int, TPerson>  $people
     * @return array<int, int>
     */
    private function ids(Collection $people): array
    {
        return $people->map(fn (Wrestler|TagTeam|Referee $person): int => $person->id)->values()->all();
    }

    /**
     * The people on the card's open matches who are unavailable on the event's day. The matches are the ones already
     * read for the card; once the event date has passed, matches with a result no longer count.
     *
     * @param  ModelCollection<int, EventMatch>  $matches
     * @return Collection<int, Wrestler|Referee>
     */
    private function calendarUnavailable(Event $event, ModelCollection $matches): Collection
    {
        $open = $event->date?->isPast() ? $matches->whereNull('match_finish') : $matches;

        return $this->availability->among($event, $this->roster->fromMatches($open, $event)['people']);
    }

    /**
     * @param  array<string, RosterRow>  $roster
     * @param  array<int, int>  $available
     */
    private function addPerson(array &$roster, Wrestler|Referee $person, string $role, int $number, array $available, bool $dateBlocked = false): void
    {
        $key = EventRosterService::key($person);
        $roster[$key] ??= ['key' => $key, 'name' => $person instanceof Wrestler ? $person->name : $person->full_name,
            'role' => $role, 'matches' => [], 'warning' => $dateBlocked || ! in_array($person->id, $available, true)];
        $roster[$key]['warning'] = $roster[$key]['warning'] || $dateBlocked;
        $roster[$key]['role'] = implode(', ', array_unique([...explode(', ', $roster[$key]['role']), $role]));
        $roster[$key]['matches'] = array_values(array_unique([...$roster[$key]['matches'], $number]));
    }
}
