<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Models\Events\Event;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Tournaments\TournamentNode;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Support\Facades\DB;

class PlanTournamentBoutAction
{
    public function __construct(private readonly TournamentLocks $locks, private readonly TournamentBracketService $bracket) {}

    public function handle(TournamentNode $node, ?Event $event, int $version): void
    {
        DB::transaction(function () use ($node, $event, $version): void {
            $tournament = $this->locks->lock($node->tournament_id, $event instanceof Event ? [$event->id] : []);
            $event = $event instanceof Event ? Event::query()->withTrashed()->findOrFail($event->id) : null;
            if ($tournament->version !== $version || ($event !== null && ($event->trashed() || $event->promotion_id !== $tournament->promotion_id))) {
                throw RecordChangedException::changed();
            }
            if (in_array($tournament->status, [TournamentStatus::Completed, TournamentStatus::Cancelled], true)) {
                throw TournamentException::closed();
            }
            $node = $tournament->nodes()->findOrFail($node->id);
            $bout = $this->bracket->bout($node);
            if ($bout->link instanceof TournamentMatchLink && $bout->link->match->event_id !== $event?->id) {
                throw TournamentException::alreadyBooked();
            }
            $node->forceFill(['planned_event_id' => $event?->id])->save();
            $tournament->forceFill(['version' => $tournament->version + 1])->save();
            activity('tournaments')->withProperties(['promotion_id' => $tournament->promotion_id, 'tournament_id' => $tournament->id, 'node_id' => $node->id, 'event_id' => $event?->id])->log('tournament.planned');
        }, attempts: 3);
    }
}
