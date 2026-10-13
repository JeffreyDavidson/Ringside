<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Enums\Events\PublicAnnouncement;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

/**
 * @phpstan-import-type Draft from EventPublication
 * @phpstan-import-type Selection from EventPublication
 * @phpstan-import-type Card from EventPublication
 * @phpstan-import-type Row from EventPublication
 *
 * @phpstan-type MatchRow array{id: int, label: string, type: string, stipulation: ?string, titles: string, finish: ?string, winner: ?string}
 * @phpstan-type Source array{fingerprint: string, schedule: string, title: string, promotion: string, date: ?string, timezone: string, venue: ?string, address: ?string, matches: list<MatchRow>}
 */
class PublicEventSourceQuery
{
    /** Public source reads explicitly constrain ownership, independently of a visitor's authenticated context.
     * @return Source
     */
    public function get(Event $event): array
    {
        $event->load(['promotion', 'venue']);
        $owner = $event->promotion_id;
        $owned = fn (Builder $query): Builder => $query->withoutGlobalScope(PromotionContextScope::class)->where('promotion_id', $owner);
        $matches = EventMatch::query()->withoutGlobalScope(PromotionContextScope::class)->forEventId($event->id)->inCardOrder()
            ->with(['matchStipulation', 'titles' => fn (Relation $query): Builder => $owned($query->getQuery())->orderBy('titles.id'), 'sides.competitors'])->get();
        $assignments = $matches->flatMap(fn (EventMatch $match) => $match->sides->flatMap(fn (MatchSide $side) => $side->competitors));
        $wrestlers = Wrestler::query()->withoutGlobalScope(PromotionContextScope::class)->where('promotion_id', $owner)
            ->whereKey($assignments->where('competitor_type', 'wrestler')->pluck('competitor_id'))->get();
        $teams = TagTeam::query()->withoutGlobalScope(PromotionContextScope::class)->where('promotion_id', $owner)
            ->whereKey($assignments->where('competitor_type', 'tag_team')->pluck('competitor_id'))->get();
        $people = $wrestlers->toBase()->concat($teams)->mapWithKeys(fn (Wrestler|TagTeam $person): array => [$person->getMorphClass().':'.$person->id => $person->name]);
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

        return [...$data, 'fingerprint' => $this->fingerprint($data), 'schedule' => $this->fingerprint([
            $event->date?->toIso8601String(), $event->venue_id, $event->venue?->name, $zone, $data['address']])];
    }

    /** @param array<array-key, mixed> $values */
    public function fingerprint(array $values): string
    {
        return hash('sha256', serialize($values));
    }

    /**
     * @param  Source  $source
     * @param  list<Selection>  $selection
     * @return list<Row>
     */
    public function rows(array $source, array $selection): array
    {
        $selected = collect($selection)->keyBy('id');
        $rows = [];
        foreach ($source['matches'] as $match) {
            $choice = $selected->get($match['id']);
            if ($choice === null) {
                continue;
            }
            $rows[] = ['label' => $match['label'], 'details' => implode(' · ', array_filter([
                $choice['type'] ? $match['type'] : null, $choice['stipulation'] ? $match['stipulation'] : null,
                $choice['titles'] ? $match['titles'] : null])), 'outcome' => null];
        }

        return $rows;
    }

    /**
     * @param  Source  $source
     * @param  list<Selection>  $selection
     */
    public function cardFingerprint(array $source, array $selection): string
    {
        return $this->fingerprint([$source['title'], $source['promotion'], $this->rows($source, $selection)]);
    }

    /**
     * @param  Source  $source
     * @param  list<int>  $ids
     * @return list<MatchRow>
     */
    public function resultSource(array $source, array $ids): array
    {
        return array_values(array_filter($source['matches'], fn (array $match): bool => in_array($match['id'], $ids, true)));
    }

    /**
     * @param  Source  $source
     * @param  Draft  $draft
     * @return Card
     */
    public function card(array $source, array $draft): array
    {
        $notice = PublicAnnouncement::from($draft['announcement'])->isNotice();

        return ['title' => $source['title'], 'promotion' => $source['promotion'], 'announcement' => $draft['announcement'],
            'summary' => $draft['summary'], 'notice' => $draft['notice'], 'date' => $notice ? null : $source['date'],
            'timezone' => $source['timezone'], 'venue' => $source['venue'], 'address' => $draft['address'] ? $source['address'] : null,
            'doors' => $notice || $draft['doors_at'] === null ? null : Carbon::parse($draft['doors_at'].$draft['doors_offset'])->toIso8601String(),
            'tickets' => $notice ? null : $draft['tickets'], 'poster' => $draft['poster'], 'rows' => $notice ? [] : $this->rows($source, $draft['matches'])];
    }
}
