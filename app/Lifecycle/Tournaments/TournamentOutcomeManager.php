<?php

declare(strict_types=1);

namespace App\Lifecycle\Tournaments;

use App\Data\Matches\MatchResultData;
use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Tournaments\TournamentException;
use App\Models\Matches\EventMatch;
use App\Models\Tournaments\Tournament;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Tournaments\TournamentNode;
use App\Models\Users\User;
use App\Services\Tournaments\TournamentBracketService;

class TournamentOutcomeManager
{
    public function __construct(private readonly TournamentBracketService $bracket, private readonly TournamentStateMachine $states) {}

    public function ensureResultCanChange(EventMatch $match, MatchResultData $result): void
    {
        $link = TournamentMatchLink::query()->with('node', 'tournament')->forMatchId($match->id)->first();

        if ($link === null) {
            return;
        }

        if ($match->match_finish === $result->finish && $match->winning_side_id === $result->winningSide?->id) {
            return;
        }

        if ($result->tournamentVersion === null || ($match->match_finish !== null && $result->tournamentVersion !== $link->tournament->version)) {
            throw RecordChangedException::changed();
        }

        $this->states->ensureCanProgress($link->tournament);

        if ($link->active_node_id === null && ($match->match_finish === null || $match->winning_side_id !== $result->winningSide?->id)) {
            throw TournamentException::historicalAttempt();
        }

        if ($match->winning_side_id === $result->winningSide?->id) {
            return;
        }

        $descendants = $this->bracket->descendantNodeIds($link->node);

        if (TournamentMatchLink::query()->whereIn('node_id', $descendants)->whereNotNull('active_node_id')->exists()) {
            throw TournamentException::dependentBookings();
        }

        if (TournamentNode::query()->whereKey($descendants)->whereNotNull('walkover_entry_id')->exists()) {
            throw TournamentException::dependentBookings();
        }
    }

    public function synchronize(Tournament $tournament, User $actor): void
    {
        if (! $this->states->canProgress($tournament)) {
            return;
        }

        $final = $this->bracket->bouts($tournament)->last();
        $tournament->forceFill(['status' => $final?->winner !== null && $final->complete ? TournamentStatus::Completed : TournamentStatus::Active, 'version' => $tournament->version + 1])->save();
        activity('tournaments')->causedBy($actor)->withProperties(['promotion_id' => $tournament->promotion_id, 'tournament_id' => $tournament->id, 'version' => $tournament->version])->log('tournament.progressed');
    }

    public function resultRecorded(EventMatch $match, User $actor): void
    {
        $link = TournamentMatchLink::query()->forMatchId($match->id)->first();

        if ($link !== null) {
            $this->synchronize($link->tournament()->firstOrFail(), $actor);
        }
    }
}
