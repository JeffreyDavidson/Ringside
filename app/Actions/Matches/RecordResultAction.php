<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Enums\MatchFinish;
use App\Lifecycle\MatchOutcomeRequirements;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
use Illuminate\Support\Facades\DB;

class RecordResultAction
{
    public function __construct(private MatchOutcomeRequirements $requirements) {}

    public function handle(EventMatch $match, MatchFinish $finish, ?MatchSide $winningSide): EventMatch
    {
        return DB::transaction(function () use ($match, $finish, $winningSide): EventMatch {
            $lockedMatch = EventMatch::query()->lockForUpdate()->findOrFail($match->id);
            $lockedWinningSide = $winningSide === null
                ? null
                : MatchSide::query()->lockForUpdate()->findOrFail($winningSide->id);

            $this->requirements->ensureSatisfied($lockedMatch, $finish, $lockedWinningSide);

            $lockedMatch->update([
                'match_finish' => $finish,
                'winning_side_id' => $lockedWinningSide?->id,
            ]);

            return $lockedMatch->refresh();
        });
    }
}
