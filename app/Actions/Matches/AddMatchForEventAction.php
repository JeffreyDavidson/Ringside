<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Data\Matches\EventMatchData;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Lifecycle\Matches\MatchConfigurationRequirements;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Services\Matches\MatchAssignmentConflictService;
use Illuminate\Support\Facades\DB;

class AddMatchForEventAction
{
    public function __construct(
        private readonly AddRefereesToMatchAction $addRefereesToMatchAction,
        private readonly AddTitlesToMatchAction $addTitlesToMatchAction,
        private readonly AddCompetitorsToMatchAction $addCompetitorsToMatchAction,
        private readonly MatchConfigurationRequirements $requirements,
        private readonly MatchAssignmentConflictService $conflictService,
    ) {}

    /** @throws EntityNotAvailableException|InvalidMatchConfigurationException */
    public function handle(Event $event, EventMatchData $eventMatchData): EventMatch
    {
        $this->requirements->ensureComplete($eventMatchData);
        $this->requirements->ensureWithinEventPromotion($event, $eventMatchData);

        return DB::transaction(function () use ($event, $eventMatchData): EventMatch {
            $this->conflictService->lockEventSet($event->id);
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

            $this->addRefereesToMatchAction->handleWithinTransaction($createdMatch, $eventMatchData->referees);

            $this->addCompetitorsToMatchAction->handleWithinTransaction($createdMatch, $eventMatchData->sides);

            if ($eventMatchData->titles->isNotEmpty()) {
                $this->addTitlesToMatchAction->handleWithinTransaction($createdMatch, $eventMatchData->titles);
            }

            return $createdMatch;
        }, attempts: 3);
    }
}
