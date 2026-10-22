<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\RecordChangedException;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Lifecycle\Tournaments\TournamentStateMachine;
use App\Models\Tournaments\Tournament;
use App\Models\Users\User;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Support\Facades\DB;

class CancelTournamentAction
{
    public function __construct(private readonly TournamentLocks $locks, private readonly TournamentBracketService $bracket, private readonly ResetTournamentBranchAction $reset, private readonly TournamentStateMachine $states) {}

    public function handle(Tournament $tournament, int $version, string $reason, User $actor): void
    {
        DB::transaction(function () use ($tournament, $version, $reason, $actor): void {
            $tournament = $this->locks->lock($tournament->id);
            if ($tournament->version !== $version) {
                throw RecordChangedException::changed();
            }
            if ($tournament->status === TournamentStatus::Cancelled) {
                return;
            }
            $this->states->ensureCanMove($tournament, TournamentStatus::Cancelled);
            foreach ($this->bracket->bouts($tournament) as $bout) {
                if ($bout->link !== null && $bout->link->match->match_finish === null && ! $bout->link->match->trashed() && $bout->link->refresh()->active_node_id !== null) {
                    $this->reset->handle($bout->node, $tournament->refresh()->version, $reason, $actor, true);
                }
            }
            $tournament->refresh()->forceFill(['status' => TournamentStatus::Cancelled, 'version' => $tournament->version + 1])->save();
            activity('tournaments')->causedBy($actor)->withProperties(['promotion_id' => $tournament->promotion_id, 'tournament_id' => $tournament->id, 'reason' => $reason])->log('tournament.cancelled');
        }, attempts: 3);
    }
}
