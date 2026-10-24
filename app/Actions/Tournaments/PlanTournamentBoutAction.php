<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Lifecycle\Tournaments\TournamentStateMachine;
use App\Models\Events\Event;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Tournaments\TournamentNode;
use App\Models\Users\User;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Support\Facades\DB;

class PlanTournamentBoutAction
{
    public function __construct(private readonly TournamentLocks $locks, private readonly TournamentBracketService $bracket, private readonly TournamentStateMachine $states) {}

    public function handle(TournamentNode $node, ?Event $event, int $version, User $actor): void
    {
        DB::transaction(function () use ($node, $event, $version, $actor): void {
            $tournament = $this->locks->lock($node->tournament_id, $event instanceof Event ? [$event->id] : []);
            $event = $event instanceof Event ? Event::query()->withTrashed()->findOrFail($event->id) : null;

            if ($tournament->version !== $version || ($event !== null && ($event->trashed() || $event->promotion_id !== $tournament->promotion_id))) {
                throw RecordChangedException::changed();
            }

            $this->states->ensureOpen($tournament);
            $node = $tournament->nodes()->findOrFail($node->id);
            $bout = $this->bracket->bout($node);

            if ($bout->link instanceof TournamentMatchLink && $bout->link->match->event_id !== $event?->id) {
                throw TournamentException::alreadyBooked();
            }

            $node->forceFill(['planned_event_id' => $event?->id])->save();
            $tournament->increment('version');

            activity('tournaments')
                ->causedBy($actor)
                ->withProperties([
                    'promotion_id' => $tournament->promotion_id,
                    'tournament_id' => $tournament->id,
                    'node_id' => $node->id,
                    'event_id' => $event?->id,
                ])
                ->log('tournament.planned');
        }, attempts: 3);
    }
}
