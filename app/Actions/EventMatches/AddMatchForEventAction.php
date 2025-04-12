<?php

declare(strict_types=1);

namespace App\Actions\EventMatches;

use App\Data\EventMatchData;
use App\Models\Event;
use App\Models\EventMatch;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

final class AddMatchForEventAction extends BaseEventMatchAction
{
    use AsAction;

    /**
     * Create a match for an event.
     */
    public function handle(Event $event, EventMatchData $eventMatchData): EventMatch
    {
        $createdMatch = $this->eventMatchRepository->createForEvent($event, $eventMatchData);

        AddRefereesToMatchAction::run($createdMatch, $eventMatchData->referees);

        $eventMatchData->titles->whenNotEmpty(function (Collection $titles) use ($createdMatch) {
            AddTitlesToMatchAction::run($createdMatch, $titles);
        });

        AddCompetitorsToMatchAction::run($createdMatch, $eventMatchData->competitors);

        return $createdMatch;
    }
}
