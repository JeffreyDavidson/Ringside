<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Data\Matches\EventMatchData;
use App\Enums\Matches\MatchType;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Exceptions\Tournaments\TournamentException;
use App\Lifecycle\Matches\MatchConfigurationRequirements;
use App\Lifecycle\Matches\MatchSchedulingLock;
use App\Lifecycle\Roster\Booking\MatchRosterLock;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Tournaments\TournamentNode;
use App\Services\Tournaments\TournamentBracketService;
use Illuminate\Support\Facades\DB;

class AddMatchForEventAction
{
    public function __construct(
        private readonly AddRefereesToMatchAction $addRefereesToMatchAction,
        private readonly AddTitlesToMatchAction $addTitlesToMatchAction,
        private readonly AddCompetitorsToMatchAction $addCompetitorsToMatchAction,
        private readonly MatchConfigurationRequirements $requirements,
        private readonly MatchSchedulingLock $schedulingLock,
        private readonly TournamentLocks $tournamentLocks,
        private readonly TournamentBracketService $bracket,
        private readonly MatchRosterLock $rosterLock,
    ) {}

    /** @throws EntityNotAvailableException|InvalidMatchConfigurationException */
    public function handle(Event $event, EventMatchData $eventMatchData, ?TournamentNode $node = null, ?int $version = null): EventMatch
    {
        $this->requirements->ensureComplete($eventMatchData);
        $this->requirements->ensureWithinEventPromotion($event, $eventMatchData);

        return DB::transaction(function () use ($event, $eventMatchData, $node, $version): EventMatch {
            $tournament = null;
            if ($node instanceof TournamentNode) {
                $tournament = $this->tournamentLocks->lock($node->tournament_id, [$event->id]);
                $event = Event::query()->withTrashed()->findOrFail($event->id);
                $node = $tournament->nodes()->findOrFail($node->id);
                if ($tournament->promotion_id !== $event->promotion_id) {
                    throw RecordChangedException::changed();
                }
                $bout = $this->bracket->bout($node);
                if ($bout->link instanceof TournamentMatchLink && $bout->link->match->match_finish === null && $this->sameBooking($bout->link->match, $event, $eventMatchData)) {
                    return $bout->link->match;
                }
                if ($tournament->version !== $version) {
                    throw RecordChangedException::changed();
                }
                if (in_array($tournament->status, [TournamentStatus::Completed, TournamentStatus::Cancelled], true)) {
                    throw TournamentException::closed();
                }
                if (! $bout->inputsResolved || ! $bout->first instanceof TournamentEntry || ! $bout->second instanceof TournamentEntry || $bout->first->withdrawn || $bout->second->withdrawn || $bout->complete) {
                    throw TournamentException::unresolved();
                }
                if ($bout->link instanceof TournamentMatchLink && $bout->link->match->match_finish === null) {
                    throw TournamentException::alreadyBooked();
                }
                $requested = $eventMatchData->sides->flatMap(fn (array $side): array => array_merge($side['wrestlers'] ?? [], $side['tag_teams'] ?? []))->map(fn (Wrestler|TagTeam $competitor): string => $competitor->getMorphClass().':'.$competitor->id)->sort()->values()->all();
                $expected = collect([$bout->first, $bout->second])->map(fn (TournamentEntry $entry): string => $entry->competitor_type.':'.$entry->competitor_id)->sort()->values()->all();
                if ($requested !== $expected || $eventMatchData->matchType !== ($tournament->entrant_kind === EntrantKind::Wrestler ? MatchType::Singles : MatchType::TagTeam)) {
                    throw TournamentException::entrantsChanged();
                }
                if ($eventMatchData->titles->isNotEmpty() && $node->round !== (int) log($tournament->capacity, 2)) {
                    throw TournamentException::finalTitlesOnly();
                }
                $priorBouts = $this->bracket->bouts($tournament)->only($this->bracket->ancestorNodeIds($node));
                if ($bout->link instanceof TournamentMatchLink) {
                    $priorBouts->put($node->id, $bout);
                }
                foreach ($priorBouts as $prior) {
                    if ($prior->link === null) {
                        continue;
                    }
                    $priorEvent = $prior->link->match->event;
                    if ($priorEvent->id === $event->id && $tournament->repeat_appearances) {
                        continue;
                    }
                    if ($event->date === null || $priorEvent->date === null || $event->date->lessThanOrEqualTo($priorEvent->date)) {
                        throw TournamentException::chronology();
                    }
                }
                if ($bout->link instanceof TournamentMatchLink) {
                    $bout->link->forceFill(['active_node_id' => null])->save();
                }
                $node->forceFill(['planned_event_id' => $event->id])->save();
            }
            $this->schedulingLock->lockEventSet($event->id);
            $lastMatch = $event->matches()
                ->withTrashed()
                ->orderByDesc('match_number')
                ->first(['match_number']);

            $createdMatch = EventMatch::query()->create([
                'event_id' => $event->id,
                'match_number' => ($lastMatch->match_number ?? 0) + 1,
                'match_type' => $eventMatchData->matchType,
                'match_stipulation_id' => $eventMatchData->matchStipulation?->id,
                'preview' => $eventMatchData->preview,
            ]);

            if ($tournament instanceof Tournament) {
                if ($tournament->status === TournamentStatus::Draft) {
                    activity('tournaments')->withProperties(['promotion_id' => $tournament->promotion_id, 'tournament_id' => $tournament->id, 'capacity' => $tournament->capacity, 'entrant_kind' => $tournament->entrant_kind->value, 'repeat_appearances' => $tournament->repeat_appearances, 'entries' => $tournament->entries()->get(['seed', 'competitor_type', 'competitor_id'])->toArray()])->log('tournament.activated');
                }
                (new TournamentMatchLink)->forceFill(['promotion_id' => $tournament->promotion_id, 'tournament_id' => $tournament->id, 'node_id' => $node->id, 'active_node_id' => $node->id, 'match_id' => $createdMatch->id, 'attempt' => ($node->links()->reorder('attempt', 'desc')->first()->attempt ?? 0) + 1])->save();
                $tournament->forceFill(['status' => TournamentStatus::Active, 'version' => $tournament->version + 1])->save();
                activity('tournaments')->withProperties(['promotion_id' => $tournament->promotion_id, 'tournament_id' => $tournament->id, 'match_id' => $createdMatch->id])->log('tournament.booked');
            }

            $this->rosterLock->forData($eventMatchData);
            $this->addRefereesToMatchAction->handleWithinTransaction($createdMatch, $eventMatchData->referees);

            $this->addCompetitorsToMatchAction->handleWithinTransaction($createdMatch, $eventMatchData->sides);

            if ($eventMatchData->titles->isNotEmpty()) {
                $this->addTitlesToMatchAction->handleWithinTransaction($createdMatch, $eventMatchData->titles);
            }

            return $createdMatch;
        }, attempts: 3);
    }

    private function sameBooking(EventMatch $match, Event $event, EventMatchData $data): bool
    {
        if ($match->trashed() || $match->event_id !== $event->id || $match->match_type !== $data->matchType || $match->match_stipulation_id !== $data->matchStipulation?->id || $match->preview !== $data->preview) {
            return false;
        }
        $requested = $data->sides->flatMap(fn (array $side, int $position): array => array_map(fn (\App\Models\Roster\Wrestlers\Wrestler|TagTeam $competitor): string => $position.':'.$competitor->getMorphClass().':'.$competitor->id, array_merge($side['wrestlers'] ?? [], $side['tag_teams'] ?? [])))->sort()->values()->all();
        $existing = $match->competitors()->with('side')->get()->map(fn (MatchCompetitor $competitor): string => $competitor->side->position.':'.$competitor->competitor_type.':'.$competitor->competitor_id)->sort()->values()->all();

        return $requested === $existing
            && $match->referees()->pluck('referees.id')->sort()->values()->all() === $data->referees->pluck('id')->sort()->values()->all()
            && $match->titles()->pluck('titles.id')->sort()->values()->all() === $data->titles->pluck('id')->sort()->values()->all();
    }
}
