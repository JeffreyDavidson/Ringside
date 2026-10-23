<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Enums\Tournaments\EntrantKind;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Lifecycle\Tournaments\TournamentOutcomeManager;
use App\Lifecycle\Tournaments\TournamentStateMachine;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Tournaments\TournamentNode;
use App\Models\Users\User;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AwardTournamentWalkoverAction
{
    public function __construct(private readonly TournamentLocks $locks, private readonly TournamentBracketService $bracket, private readonly TournamentOutcomeManager $outcomes, private readonly RosterBookingEligibility $eligibility, private readonly TournamentStateMachine $states) {}

    public function handle(TournamentNode $node, int $version, string $reason, User $actor): void
    {
        DB::transaction(function () use ($node, $version, $reason, $actor): void {
            $tournament = $this->locks->lock($node->tournament_id);

            if ($tournament->version !== $version) {
                throw RecordChangedException::changed();
            }

            $this->states->ensureActive($tournament);
            $node = $tournament->nodes()->findOrFail($node->id);
            $bout = $this->bracket->bout($node);

            if (! $bout->inputsResolved || ! $bout->first instanceof TournamentEntry || ! $bout->second instanceof TournamentEntry || $bout->complete || $bout->link instanceof TournamentMatchLink || $bout->first->withdrawn === $bout->second->withdrawn) {
                throw TournamentException::walkoverInvalid();
            }

            $winner = $bout->first->withdrawn ? $bout->second : $bout->first;
            $competitor = $tournament->entrant_kind->modelClass()::query()
                ->whereKey($winner->competitor_id)
                ->when($tournament->entrant_kind === EntrantKind::TagTeam, fn (Builder $query) => $query->with('currentWrestlers'))
                ->lockForUpdate()
                ->first();

            if ($competitor === null || ! $this->eligibility->allows($competitor)) {
                throw TournamentException::entrantUnavailable();
            }

            $node->fill(['walkover_reason' => $reason]);
            $node->walkover_entry_id = $winner->id;
            $node->save();

            activity('tournaments')
                ->causedBy($actor)
                ->withProperties([
                    'promotion_id' => $tournament->promotion_id,
                    'tournament_id' => $tournament->id,
                    'node_id' => $node->id,
                    'winner_entry_id' => $winner->id,
                    'reason' => $reason,
                ])
                ->log('tournament.walkover');

            $this->outcomes->synchronize($tournament, $actor);
        }, attempts: 3);
    }
}
