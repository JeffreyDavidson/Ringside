<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Enums\Tournaments\EntrantKind;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Lifecycle\Tournaments\TournamentStateMachine;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Users\User;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReplaceTournamentEntryAction
{
    public function __construct(private readonly TournamentLocks $locks, private readonly TournamentBracketService $bracket, private readonly ResetTournamentBranchAction $reset, private readonly RosterBookingEligibility $eligibility, private readonly TournamentStateMachine $states) {}

    public function handle(TournamentEntry $entry, int $competitorId, int $version, string $reason, User $actor): void
    {
        DB::transaction(function () use ($entry, $competitorId, $version, $reason, $actor): void {
            $tournament = $this->locks->lock($entry->tournament_id);

            if ($tournament->version !== $version) {
                throw RecordChangedException::changed();
            }

            $this->states->ensureActive($tournament);
            $entry = $tournament->entries()->findOrFail($entry->id);

            foreach ($this->bracket->bouts($tournament) as $bout) {
                if (($bout->first?->id === $entry->id || $bout->second?->id === $entry->id) && ($bout->link?->match->match_finish !== null || $bout->node->walkover_entry_id !== null)) {
                    throw TournamentException::entryPlayed();
                }
            }

            $competitor = $tournament->entrant_kind->modelClass()::query()->where('promotion_id', $tournament->promotion_id)->whereKey($competitorId)->when($tournament->entrant_kind === EntrantKind::TagTeam, fn (Builder $query) => $query->with('currentWrestlers'))->first();

            if ($competitor === null || $tournament->entries()->where('competitor_id', $competitorId)->whereKeyNot($entry->id)->exists()) {
                throw TournamentException::entrantUnavailable();
            }

            $node = $tournament->nodes()->where('round', 1)->where(fn (Builder $query) => $query->where('first_seed', $entry->seed)->orWhere('second_seed', $entry->seed))->firstOrFail();
            $oldId = $entry->competitor_id;
            $this->reset->handle($node, $tournament->version, $reason, $actor, true);
            $competitor = $tournament->entrant_kind->modelClass()::query()
                ->whereKey($competitorId)
                ->when($tournament->entrant_kind === EntrantKind::TagTeam, fn (Builder $query) => $query->with('currentWrestlers'))
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->eligibility->allows($competitor)) {
                throw TournamentException::entrantUnavailable();
            }

            $entry->forceFill(['competitor_id' => $competitorId, 'withdrawn' => false])->save();
            Tournament::query()->whereKey($tournament->id)->increment('version');

            activity('tournaments')
                ->causedBy($actor)
                ->withProperties([
                    'promotion_id' => $tournament->promotion_id,
                    'tournament_id' => $tournament->id,
                    'entry_id' => $entry->id,
                    'previous_competitor_id' => $oldId,
                    'competitor_id' => $competitorId,
                    'reason' => $reason,
                ])
                ->log('tournament.replaced');
        }, attempts: 3);
    }
}
