<?php

declare(strict_types=1);

namespace App\Actions\Tournaments;

use App\Data\Tournaments\TournamentData;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Lifecycle\Tournaments\TournamentStateMachine;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentNode;
use App\ValueObjects\BracketShape;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SaveTournamentAction
{
    public function __construct(private readonly RosterBookingEligibility $eligibility, private readonly TournamentStateMachine $states) {}

    public function handle(Promotion $promotion, TournamentData $data, ?Tournament $tournament = null, ?int $version = null): Tournament
    {
        return DB::transaction(function () use ($promotion, $data, $tournament, $version): Tournament {
            if (! $tournament instanceof Tournament && $data->requestId !== null) {
                Promotion::query()->whereKey($promotion->id)->lockForUpdate()->firstOrFail();
                $existing = Tournament::query()->where('promotion_id', $promotion->id)->where('request_id', $data->requestId)->first();
                if ($existing !== null) {
                    $acceptedIds = array_values($existing->entries()->get()->map(fn (TournamentEntry $entry): int => $entry->competitor_id)->all());
                    if ($existing->name !== mb_trim($data->name) || $existing->description !== $data->description || $existing->entrant_kind !== $data->kind || $existing->capacity !== $data->capacity || $existing->repeat_appearances !== $data->repeatAppearances || $acceptedIds !== $data->competitorIds) {
                        throw RecordChangedException::changed();
                    }

                    return $existing;
                }
            }
            $locked = $tournament instanceof Tournament ? Tournament::query()->lockForUpdate()->findOrFail($tournament->id) : new Tournament;
            if ($locked->exists && ($locked->promotion_id !== $promotion->id || $locked->version !== $version)) {
                throw RecordChangedException::changed();
            }
            if ($locked->exists) {
                $this->states->ensureStructureEditable($locked);
            }
            $competitors = $data->kind->modelClass()::query()->where('promotion_id', $promotion->id)->whereKey($data->competitorIds)->orderBy('id')->when($data->kind === EntrantKind::TagTeam, fn (Builder $query) => $query->with('currentWrestlers'))->lockForUpdate()->get();
            if ($competitors->count() !== count($data->competitorIds) || $competitors->contains(fn (\App\Models\Roster\Wrestlers\Wrestler|\App\Models\Roster\Referees\Referee|TagTeam $competitor): bool => ! $this->eligibility->allows($competitor))) {
                throw TournamentException::entrantUnavailable();
            }
            $locked->fill([
                'name' => mb_trim($data->name),
                'description' => $data->description,
                'entrant_kind' => $data->kind,
                'capacity' => $data->capacity,
                'repeat_appearances' => $data->repeatAppearances,
            ]);
            $locked->promotion_id = $promotion->id;
            $locked->request_id = $locked->exists ? $locked->request_id : $data->requestId;
            $locked->status = TournamentStatus::Draft;
            $locked->version = $locked->exists ? $locked->version + 1 : 1;
            $locked->save();
            $locked->nodes()->delete();
            $locked->entries()->delete();
            foreach ($data->competitorIds as $offset => $id) {
                $competitor = $competitors->sole('id', $id);
                (new TournamentEntry)->forceFill(['promotion_id' => $promotion->id, 'tournament_id' => $locked->id, 'seed' => $offset + 1, 'competitor_type' => $competitor->getMorphClass(), 'competitor_id' => $id])->save();
            }
            foreach (new BracketShape($data->capacity)->slots() as [$round, $position, $firstSeed, $secondSeed]) {
                (new TournamentNode)->forceFill(['promotion_id' => $promotion->id, 'tournament_id' => $locked->id, 'round' => $round, 'position' => $position, 'first_seed' => $firstSeed, 'second_seed' => $secondSeed])->save();
            }
            activity('tournaments')->withProperties(['promotion_id' => $promotion->id, 'tournament_id' => $locked->id, 'version' => $locked->version])->log('tournament.saved');

            return $locked;
        }, attempts: 3);
    }
}
