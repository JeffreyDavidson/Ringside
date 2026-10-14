<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

/**
 * @phpstan-type CardRow array{id: int, number: int, label: string, details: string, referees: string, warning: bool}
 * @phpstan-type RosterRow array{key: string, name: string, role: string, matches: list<int>, warning: bool}
 * @phpstan-type Card array{fingerprint: string, timezone: string, rows: list<CardRow>, roster: list<RosterRow>}
 */
class EventPackCardQuery
{
    public function __construct(private readonly EventRosterAvailabilityQuery $calendar) {}

    /** @return Card */
    public function get(Event $event): array
    {
        $event->load(['promotion', 'venue']);
        $matches = EventMatch::query()->forEventId($event->id)->inCardOrder()->withDisplayRelations()
            ->with(['matchStipulation', 'referees' => fn (Relation $query): Builder => $query->getQuery()->withoutGlobalScope(SoftDeletingScope::class)->orderBy('referees.id')])->get();
        $teams = new ModelCollection($matches->flatMap(fn (EventMatch $match): Collection => $match->competitors->tagTeams())->unique('id')->all());
        $teams->load(['currentWrestlers' => fn (Relation $query): Builder => $query->getQuery()->withoutGlobalScope(SoftDeletingScope::class)->orderBy('wrestlers.id')]);
        $wrestlers = $matches->flatMap(fn (EventMatch $match): Collection => $match->competitors->wrestlers())
            ->merge($teams->flatMap(fn (TagTeam $team): ModelCollection => $team->currentWrestlers))->unique('id');
        $referees = $matches->flatMap(fn (EventMatch $match): ModelCollection => $match->referees)->unique('id');
        $available = [
            'wrestler' => Wrestler::query()->bookable()->whereKey($wrestlers->pluck('id')->all())->get()->map(fn (Wrestler|TagTeam|Referee $person): int => $person->id)->values()->all(),
            'tag_team' => TagTeam::query()->bookable()->whereKey($teams->modelKeys())->get()->map(fn (Wrestler|TagTeam|Referee $person): int => $person->id)->values()->all(),
            'referee' => Referee::query()->bookable()->whereKey($referees->pluck('id')->all())->get()->map(fn (Wrestler|TagTeam|Referee $person): int => $person->id)->values()->all(),
        ];
        $calendarUnavailable = $this->calendar->unavailable($event)->map(RosterDateAvailability::key(...))->all();
        $rows = [];
        $roster = [];
        foreach ($matches as $match) {
            $warning = false;
            $sides = $match->competitors->competitorModelsBySidePosition();
            foreach ($match->competitors as $assignment) {
                $competitor = $assignment->competitor;
                $calendarRelevant = $match->match_finish === null || $event->date >= now();
                $dateBlocked = $calendarRelevant && in_array(RosterDateAvailability::key($competitor), $calendarUnavailable, true);
                $warning = $warning || $dateBlocked || ! in_array($competitor->id, $available[$competitor->getMorphClass()], true);
                if ($competitor instanceof TagTeam) {
                    $team = $teams->firstOrFail(fn (TagTeam $team): bool => $team->id === $competitor->id);
                    foreach ($team->currentWrestlers as $wrestler) {
                        $blocked = $calendarRelevant && in_array(RosterDateAvailability::key($wrestler), $calendarUnavailable, true);
                        $warning = $warning || $blocked;
                        $this->addPerson($roster, $wrestler, $competitor->name, $match->match_number, $available['wrestler'], $blocked);
                    }

                    continue;
                }
                $this->addPerson($roster, $competitor, __('event-pack.wrestler'), $match->match_number, $available['wrestler'], $dateBlocked);
            }
            foreach ($match->referees as $referee) {
                $dateBlocked = ($match->match_finish === null || $event->date >= now()) && in_array(RosterDateAvailability::key($referee), $calendarUnavailable, true);
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
        $zone = Promotion::zoneOf($event->promotion);
        $roster = array_values($roster);

        return [
            'fingerprint' => hash('sha256', serialize([$event->name, $event->date?->toIso8601String(), $event->venue_id, $event->venue?->name, $zone, $rows, $roster])),
            'timezone' => $zone, 'rows' => $rows, 'roster' => $roster,
        ];
    }

    /**
     * @param  array<string, RosterRow>  $roster
     * @param  array<int, int>  $available
     */
    private function addPerson(array &$roster, Wrestler|Referee $person, string $role, int $number, array $available, bool $dateBlocked = false): void
    {
        $key = $person->getMorphClass().':'.$person->id;
        $roster[$key] ??= ['key' => $key, 'name' => $person instanceof Wrestler ? $person->name : $person->full_name,
            'role' => $role, 'matches' => [], 'warning' => $dateBlocked || ! in_array($person->id, $available, true)];
        $roster[$key]['warning'] = $roster[$key]['warning'] || $dateBlocked;
        $roster[$key]['role'] = implode(', ', array_unique([...explode(', ', $roster[$key]['role']), $role]));
        $roster[$key]['matches'] = array_values(array_unique([...$roster[$key]['matches'], $number]));
    }
}
