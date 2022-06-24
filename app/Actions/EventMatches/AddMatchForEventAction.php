<?php

declare(strict_types=1);

namespace App\Actions\EventMatches;

use App\Data\EventMatchData;
use App\Models\Event;
use Lorisleiva\Actions\Concerns\AsAction;

class AddMatchForEventAction extends BaseEventMatchAction
{
    use AsAction;

    /**
     * Undocumented function.
     *
     * @param  \App\Models\Event $event
     * @param  \App\Data\EventMatchData $eventMatchData
     * @return \App\Models\EventMatch $cratedMatch
     */
    public function handle(Event $event, EventMatchData $eventMatchData)
    {
        /** @var \App\Models\EventMatch $createdMatch */
        $createdMatch = $this->eventMatchRepository->createForEvent($event, $eventMatchData);

        AddRefereesToMatchAction::run($createdMatch, $eventMatchData->referees);

        $eventMatchData->titles->whenNotEmpty(function ($titles) use ($createdMatch) {
            AddTitlesToMatchAction::run($createdMatch, $titles);
        });

        AddCompetitorsToMatchAction::run($createdMatch, $eventMatchData->competitors);

        return $createdMatch;
    }
}
