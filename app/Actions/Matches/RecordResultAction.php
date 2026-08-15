<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Data\Matches\MatchEliminationData;
use App\Data\Matches\MatchResultData;
use App\Lifecycle\MatchOutcomeRequirements;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use Illuminate\Support\Facades\DB;

class RecordResultAction
{
    public function __construct(private MatchOutcomeRequirements $requirements) {}

    public function handle(EventMatch $match, MatchResultData $result): EventMatch
    {
        return DB::transaction(function () use ($match, $result): EventMatch {
            $lockedMatch = EventMatch::query()->lockForUpdate()->findOrFail($match->id);
            $lockedWinningSide = $result->winningSide === null
                ? null
                : MatchSide::query()->lockForUpdate()->findOrFail($result->winningSide->id);
            $lockedCompetitors = MatchCompetitor::query()
                ->whereBelongsTo($lockedMatch, 'eventMatch')
                ->lockForUpdate()
                ->get();
            $lockedResult = new MatchResultData(
                finish: $result->finish,
                winningSide: $lockedWinningSide,
                eliminations: $result->eliminations,
            );

            $this->requirements->ensureSatisfied($lockedMatch, $lockedResult, $lockedCompetitors);

            $lockedMatch->update([
                'match_finish' => $result->finish,
                'winning_side_id' => $lockedWinningSide?->id,
            ]);

            MatchCompetitor::query()
                ->whereBelongsTo($lockedMatch, 'eventMatch')
                ->update([
                    'elimination_order' => null,
                    'eliminated_by_match_competitor_id' => null,
                ]);

            $result->eliminations->each(function (MatchEliminationData $elimination) use ($lockedCompetitors): void {
                $lockedCompetitor = $lockedCompetitors->sole('id', $elimination->competitor->id);

                $lockedCompetitor->forceFill([
                    'elimination_order' => $elimination->order,
                    'eliminated_by_match_competitor_id' => $elimination->eliminatedBy?->id,
                ])->save();
            });

            return $lockedMatch->refresh();
        });
    }
}
