<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Exceptions\RecordChangedException;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Lifecycle\Tournaments\TournamentOutcomeManager;
use App\Lifecycle\Tournaments\TournamentStateMachine;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Users\User;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Support\Facades\DB;

class WithdrawTournamentEntryAction
{
    public function __construct(private readonly TournamentLocks $locks, private readonly TournamentBracketService $bracket, private readonly ResetTournamentBranchAction $reset, private readonly TournamentOutcomeManager $outcomes, private readonly TournamentStateMachine $states) {}

    public function handle(TournamentEntry $entry, int $version, string $reason, User $actor): void
    {
        DB::transaction(function () use ($entry, $version, $reason, $actor): void {
            $tournament = $this->locks->lock($entry->tournament_id);

            if ($tournament->version !== $version) {
                throw RecordChangedException::changed();
            }

            $this->states->ensureActive($tournament);
            $entry = $tournament->entries()->findOrFail($entry->id);

            if ($entry->withdrawn) {
                return;
            }

            foreach ($this->bracket->bouts($tournament) as $bout) {
                if ($bout->complete || ($bout->first?->id !== $entry->id && $bout->second?->id !== $entry->id)) {
                    continue;
                }

                if ($bout->link !== null) {
                    $this->reset->handle($bout->node, $tournament->refresh()->version, $reason, $actor, true);
                }
            }

            $entry->forceFill(['withdrawn' => true])->save();

            activity('tournaments')
                ->causedBy($actor)
                ->withProperties([
                    'promotion_id' => $tournament->promotion_id,
                    'tournament_id' => $tournament->id,
                    'entry_id' => $entry->id,
                    'reason' => $reason,
                ])
                ->log('tournament.withdrawn');

            $this->outcomes->synchronize($tournament->refresh(), $actor);
        }, attempts: 3);
    }
}
