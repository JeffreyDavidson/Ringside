<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Collections\MatchCompetitorsCollection;
use App\Data\Matches\MatchEliminationData;
use App\Data\Matches\MatchResultData;
use App\Data\Matches\RecordedMatchResultData;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Lifecycle\Matches\MatchOutcomeRequirements;
use App\Lifecycle\Matches\MatchSchedulingLock;
use App\Lifecycle\Roster\Booking\MatchRosterLock;
use App\Lifecycle\Tournaments\TournamentOutcomeManager;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecordResultAction
{
    public function __construct(
        private readonly MatchOutcomeRequirements $requirements,
        private readonly ApplyMatchTitleOutcomesAction $applyTitleOutcomes,
        private readonly MatchSchedulingLock $schedulingLock,
        private readonly TournamentOutcomeManager $tournaments,
        private readonly MatchRosterLock $rosterLock,
    ) {}

    public function handle(EventMatch $match, MatchResultData $result, User $actor): RecordedMatchResultData
    {
        return DB::transaction(function () use ($match, $result, $actor): RecordedMatchResultData {
            $lockedMatch = $this->schedulingLock->lockMatchWithEventSet($match);
            $this->tournaments->ensureResultCanChange($lockedMatch, $result);
            $changed = $lockedMatch->match_finish !== $result->finish || $lockedMatch->winning_side_id !== $result->winningSide?->id;
            $lockedMatch->setRelation('event', Event::query()->findOrFail($lockedMatch->event_id));

            $eventDate = $lockedMatch->event->date;

            if (! $eventDate instanceof Carbon || $eventDate->isFuture()) {
                throw InvalidMatchOutcomeException::eventNotHeld();
            }

            $lockedWinningSide = $result->winningSide instanceof MatchSide
                ? $result->winningSide->refreshForUpdate()
                : null;
            $lockedCompetitors = MatchCompetitor::query()
                ->whereBelongsTo($lockedMatch, 'eventMatch')
                ->with('competitor')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lockedWinningSide instanceof MatchSide) {
                $this->lockWinners($lockedCompetitors->where('match_side_id', $lockedWinningSide->id));
            }

            $lockedResult = new MatchResultData(
                finish: $result->finish,
                winningSide: $lockedWinningSide,
                eliminations: $result->eliminations,
                tournamentVersion: $result->tournamentVersion,
            );

            $this->requirements->ensureSatisfied($lockedMatch, $lockedResult, $lockedCompetitors);
            if (! $changed && TournamentMatchLink::query()->forMatchId($lockedMatch->id)->exists()) {
                return new RecordedMatchResultData($lockedMatch, new Collection);
            }
            $titlesLeftUnchanged = $this->applyTitleOutcomes->handle($lockedMatch, $lockedResult, $lockedCompetitors);

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

            $this->tournaments->resultRecorded($lockedMatch, $actor);

            return new RecordedMatchResultData($lockedMatch->refresh(), $titlesLeftUnchanged);
        });
    }

    /**
     * Lock the winners' roster rows (tag teams before their members, ascending ids) before any title or reign lock, so a
     * release or retirement of a winner cannot slip between the eligibility read and the reign write. The winners are
     * then reloaded so eligibility and back-fill checks read the locked state.
     *
     * @param  MatchCompetitorsCollection<int, MatchCompetitor>  $winners
     */
    private function lockWinners(MatchCompetitorsCollection $winners): void
    {
        $this->rosterLock->lock(collect(), $winners->wrestlers(), $winners->tagTeams());
        $winners->load('competitor');
    }
}
