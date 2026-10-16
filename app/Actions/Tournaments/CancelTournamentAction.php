<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Models\Tournaments\Tournament;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CancelTournamentAction
{
    public function __construct(private readonly TournamentLocks $locks, private readonly TournamentBracketService $bracket, private readonly ResetTournamentBranchAction $reset) {}

    public function handle(Tournament $tournament, int $version, string $reason): void
    {
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:2000']])->validate();
        DB::transaction(function () use ($tournament, $version, $reason): void {
            $tournament = $this->locks->lock($tournament->id);
            if ($tournament->version !== $version) {
                throw RecordChangedException::changed();
            }
            if ($tournament->status === TournamentStatus::Completed) {
                throw TournamentException::because('closed');
            }
            if ($tournament->status === TournamentStatus::Cancelled) {
                return;
            }
            foreach ($this->bracket->bouts($tournament) as $bout) {
                if ($bout->link !== null && $bout->link->match->match_finish === null && ! $bout->link->match->trashed() && $bout->link->refresh()->active_node_id !== null) {
                    $this->reset->handle($bout->node, $tournament->refresh()->version, $reason, true);
                }
            }
            $tournament->refresh()->forceFill(['status' => TournamentStatus::Cancelled, 'version' => $tournament->version + 1])->save();
            activity('tournaments')->withProperties(['promotion_id' => $tournament->promotion_id, 'tournament_id' => $tournament->id, 'reason' => $reason])->log('tournament.cancelled');
        }, attempts: 3);
    }
}
