<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Data\Matches\EventMatchData;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Lifecycle\MatchConfigurationRequirements;
use App\Models\Matches\EventMatch;
use Illuminate\Support\Facades\DB;

class UpdateMatchAction
{
    public function __construct(
        private AddRefereesToMatchAction $addRefereesToMatchAction,
        private AddTitlesToMatchAction $addTitlesToMatchAction,
        private AddCompetitorsToMatchAction $addCompetitorsToMatchAction,
        private MatchConfigurationRequirements $requirements,
    ) {}

    public function handle(EventMatch $match, EventMatchData $data): EventMatch
    {
        $this->requirements->ensureComplete($data);

        return DB::transaction(function () use ($match, $data): EventMatch {
            $lockedMatch = EventMatch::query()
                ->whereKey($match->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedMatch->match_finish !== null) {
                throw InvalidMatchConfigurationException::resultAlreadyRecorded();
            }

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
        });
    }
}
