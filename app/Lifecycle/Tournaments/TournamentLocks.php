<?php

declare(strict_types=1);

namespace App\Lifecycle\Tournaments;

use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Tournaments\TournamentNode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TournamentLocks
{
    /** @param list<int> $eventIds */
    public function lock(int $tournamentId, array $eventIds = []): Tournament
    {
        $this->lockMany([$tournamentId], $eventIds);

        return Tournament::query()->findOrFail($tournamentId);
    }

    /**
     * @param  list<int>  $tournamentIds
     * @param  list<int>  $extraEvents
     */
    public function lockMany(array $tournamentIds, array $extraEvents = [], ?Carbon $targetDate = null): void
    {
        $tournaments = Tournament::query()->whereKey($tournamentIds)->orderBy('id')->get();

        if ($tournaments->count() !== count(array_unique($tournamentIds))) {
            throw RecordChangedException::changed();
        }
        $snapshot = $this->eventIds($tournamentIds);
        $ids = array_values(array_unique([...$snapshot, ...$extraEvents]));
        $dates = Event::query()->withTrashed()->whereKey($ids)->whereNotNull('date')->pluck('date')->all();

        if ($targetDate instanceof Carbon) {
            $dates[] = $targetDate;
        }
        $before = Event::query()->withTrashed()->whereKey($ids)->orderBy('id')->get()->mapWithKeys(fn (Event $event): array => [$event->id => $event->date?->toIso8601String()])->all();
        Event::query()->withTrashed()->where(fn (Builder $query) => $query->whereIn('id', $ids)->orWhereIn('date', $dates))->orderBy('id')->lockForUpdate()->get(['id']);
        Tournament::query()->whereKey($tournamentIds)->orderBy('id')->lockForUpdate()->get();
        $after = Event::query()->withTrashed()->whereKey($ids)->orderBy('id')->get()->mapWithKeys(fn (Event $event): array => [$event->id => $event->date?->toIso8601String()])->all();

        if ($snapshot !== $this->eventIds($tournamentIds) || $before !== $after) {
            throw RecordChangedException::changed();
        }
    }

    /** @return list<int> */
    public function forEvent(int $eventId): array
    {
        return array_values(TournamentNode::query()->where('planned_event_id', $eventId)->get()->map(fn (TournamentNode $node): int => $node->tournament_id)->merge(
            TournamentMatchLink::query()->whereHas('match', fn (Builder $query) => $query->where('event_id', $eventId))->get()->map(fn (TournamentMatchLink $link): int => $link->tournament_id),
        )->unique()->sort()->values()->all());
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function eventIds(array $ids): array
    {
        return array_values(TournamentNode::query()->whereIn('tournament_id', $ids)->get()->map(fn (TournamentNode $node): ?int => $node->planned_event_id)->filter()->merge(
            TournamentMatchLink::query()->whereIn('tournament_id', $ids)->with('match')->get()->map(fn (TournamentMatchLink $link): int => $link->match->event_id),
        )->unique()->sort()->values()->all());
    }
}
