<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Data\Matches\EventMatchData;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use Illuminate\Support\Facades\DB;

class AddMatchForEventAction
{
    public function __construct(
        protected AddRefereesToMatchAction $addRefereesToMatchAction,
        protected AddTitlesToMatchAction $addTitlesToMatchAction,
        protected AddCompetitorsToMatchAction $addCompetitorsToMatchAction
    ) {}

    /** @throws EntityNotAvailableException|InvalidMatchConfigurationException */
    public function handle(Event $event, EventMatchData $eventMatchData): EventMatch
    {
        $this->validateMatchData($eventMatchData);

        return DB::transaction(function () use ($event, $eventMatchData): EventMatch {
            $lockedEvent = Event::query()
                ->lockForUpdate()
                ->findOrFail($event->getKey());
            $lastMatchNumber = $lockedEvent->matches()
                ->withTrashed()
                ->max('match_number');

            $createdMatch = EventMatch::query()->create([
                'event_id' => $lockedEvent->id,
                'match_number' => ($lastMatchNumber ?? 0) + 1,
                'match_type' => $eventMatchData->matchType,
                'match_stipulation_id' => $eventMatchData->matchStipulation?->id,
                'preview' => $eventMatchData->preview,
            ]);

            $this->addRefereesToMatchAction->handle($createdMatch, $eventMatchData->referees);

            if ($eventMatchData->titles->isNotEmpty()) {
                $this->addTitlesToMatchAction->handle($createdMatch, $eventMatchData->titles);
            }

            $this->addCompetitorsToMatchAction->handle($createdMatch, $eventMatchData->sides);

            return $createdMatch;
        });
    }

    /**
     * Validate match data for completeness and business rules.
     *
     * @param  EventMatchData  $eventMatchData  The match data to validate
     * @throws InvalidMatchConfigurationException When validation fails
     */
    private function validateMatchData(EventMatchData $eventMatchData): void
    {
        if ($eventMatchData->sides->isEmpty()) {
            throw InvalidMatchConfigurationException::missingCompetitors();
        }

        if ($eventMatchData->referees->isEmpty()) {
            throw InvalidMatchConfigurationException::missingReferees();
        }
    }
}
