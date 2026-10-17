<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Data\Matches\EventMatchData;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Lifecycle\Matches\MatchConfigurationRequirements;
use App\Lifecycle\Matches\MatchSchedulingLock;
use App\Lifecycle\Roster\Booking\MatchRosterLock;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Lifecycle\Tournaments\TournamentMatchRequirements;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Tournaments\TournamentMatchLink;
use Illuminate\Support\Facades\DB;

class UpdateMatchAction
{
    public function __construct(
        private readonly AddRefereesToMatchAction $addRefereesToMatchAction,
        private readonly AddTitlesToMatchAction $addTitlesToMatchAction,
        private readonly AddCompetitorsToMatchAction $addCompetitorsToMatchAction,
        private readonly MatchConfigurationRequirements $requirements,
        private readonly MatchSchedulingLock $schedulingLock,
        private readonly TournamentMatchRequirements $tournamentRequirements,
        private readonly MatchRosterLock $rosterLock,
    ) {}

    public function handle(EventMatch $match, EventMatchData $data): EventMatch
    {
        $this->requirements->ensureComplete($data);

        return DB::transaction(function () use ($match, $data): EventMatch {
            $lockedMatch = $this->schedulingLock->lockMatchWithEventSet($match);

            if ($lockedMatch->match_finish !== null) {
                throw InvalidMatchConfigurationException::resultAlreadyRecorded();
            }

            $this->requirements->ensureWithinEventPromotion($lockedMatch->event()->firstOrFail(), $data);

            $this->tournamentRequirements->ensureEditable($lockedMatch, $data);
            $this->rosterLock->forData($data);
            $retainedKeys = array_values($lockedMatch->competitors()->get()->map(fn (MatchCompetitor $entry): string => $entry->competitor_type.':'.$entry->competitor_id)
                ->merge($lockedMatch->referees()->get()->map(RosterDateAvailability::key(...)))->all());

            $lockedMatch->update([
                'match_type' => $data->matchType,
                'match_stipulation_id' => $data->matchStipulation?->id,
                'preview' => $data->preview,
            ]);

            $lockedMatch->referees()->detach();
            $lockedMatch->titles()->detach();
            $lockedMatch->competitors()->delete();
            $lockedMatch->sides()->delete();

            $this->addRefereesToMatchAction->handleWithinTransaction($lockedMatch, $data->referees, $retainedKeys);

            $this->addCompetitorsToMatchAction->handleWithinTransaction($lockedMatch, $data->sides, $retainedKeys);

            if ($data->titles->isNotEmpty()) {
                $this->addTitlesToMatchAction->handleWithinTransaction($lockedMatch, $data->titles);
            }

            $link = TournamentMatchLink::query()->forMatchId($lockedMatch->id)->first();
            if ($link !== null) {
                $tournament = $link->tournament()->firstOrFail();
                $tournament->forceFill(['version' => $tournament->version + 1])->save();
            }

            return $lockedMatch->refresh();
        }, attempts: 3);
    }
}
