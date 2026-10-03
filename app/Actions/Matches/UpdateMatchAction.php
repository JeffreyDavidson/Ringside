<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Data\Matches\EventMatchData;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Lifecycle\Matches\MatchConfigurationRequirements;
use App\Models\Matches\EventMatch;
use App\Services\Matches\MatchAssignmentConflictService;
use Illuminate\Support\Facades\DB;

class UpdateMatchAction
{
    public function __construct(
        private readonly AddRefereesToMatchAction $addRefereesToMatchAction,
        private readonly AddTitlesToMatchAction $addTitlesToMatchAction,
        private readonly AddCompetitorsToMatchAction $addCompetitorsToMatchAction,
        private readonly MatchConfigurationRequirements $requirements,
        private readonly MatchAssignmentConflictService $conflictService,
    ) {}

    public function handle(EventMatch $match, EventMatchData $data): EventMatch
    {
        $this->requirements->ensureComplete($data);

        return DB::transaction(function () use ($match, $data): EventMatch {
            $lockedMatch = $this->conflictService->lockMatchWithEventSet($match);

            if ($lockedMatch->match_finish !== null) {
                throw InvalidMatchConfigurationException::resultAlreadyRecorded();
            }

            $this->requirements->ensureWithinEventPromotion($lockedMatch->event()->firstOrFail(), $data);

            $lockedMatch->update([
                'match_type' => $data->matchType,
                'match_stipulation_id' => $data->matchStipulation?->id,
                'preview' => $data->preview,
            ]);

            $lockedMatch->referees()->detach();
            $lockedMatch->titles()->detach();
            $lockedMatch->competitors()->delete();
            $lockedMatch->sides()->delete();

            $this->addRefereesToMatchAction->handleWithinTransaction($lockedMatch, $data->referees);

            $this->addCompetitorsToMatchAction->handleWithinTransaction($lockedMatch, $data->sides);

            if ($data->titles->isNotEmpty()) {
                $this->addTitlesToMatchAction->handleWithinTransaction($lockedMatch, $data->titles);
            }

            return $lockedMatch->refresh();
        }, attempts: 3);
    }
}
