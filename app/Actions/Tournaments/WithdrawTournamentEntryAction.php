<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Lifecycle\Tournaments\TournamentOutcomeManager;
use App\Models\Tournaments\TournamentEntry;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WithdrawTournamentEntryAction
{
    public function __construct(private readonly TournamentLocks $locks, private readonly TournamentBracketService $bracket, private readonly ResetTournamentBranchAction $reset, private readonly TournamentOutcomeManager $outcomes) {}

    public function handle(TournamentEntry $entry, int $version, string $reason): void
    {
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:2000']])->validate();
        DB::transaction(function () use ($entry, $version, $reason): void {
            $tournament = $this->locks->lock($entry->tournament_id);
            if ($tournament->version !== $version) {
                throw RecordChangedException::changedKeepingInput();
            }
            if ($tournament->status !== TournamentStatus::Active) {
                throw TournamentException::closed();
            }
            $entry = $tournament->entries()->findOrFail($entry->id);
            if ($entry->withdrawn) {
                return;
            }
            foreach ($this->bracket->bouts($tournament) as $bout) {
                if ($bout->complete || ($bout->first?->id !== $entry->id && $bout->second?->id !== $entry->id)) {
                    continue;
                }
                if ($bout->link !== null) {
                    $this->reset->handle($bout->node, $tournament->refresh()->version, $reason, true);
                }
            }
            $entry->forceFill(['withdrawn' => true])->save();
            activity('tournaments')->withProperties(['promotion_id' => $tournament->promotion_id, 'tournament_id' => $tournament->id, 'entry_id' => $entry->id, 'reason' => $reason])->log('tournament.withdrawn');
            $this->outcomes->synchronize($tournament->refresh());
        }, attempts: 3);
    }
}
