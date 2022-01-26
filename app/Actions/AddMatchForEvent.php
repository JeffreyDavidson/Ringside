<?php

namespace App\Actions;

use App\DataTransferObjects\EventMatchData;
use App\Models\Event;
use App\Models\EventMatchCompetitor;
use App\Models\Referee;
use App\Models\Title;
use App\Repositories\EventMatchRepository;
use Illuminate\Support\Facades\Log;

class AddMatchForEvent
{
    /**
     * The repository to save event matches.
     *
     * @var \App\Repositories\EventMatchRepository
     */
    private EventMatchRepository $eventMatchRepository;

    /**
     * Create a new add match for event instance.
     *
     * @param \App\Repositories\EventMatchRepository $eventMatchRepository
     */
    public function __construct(EventMatchRepository $eventMatchRepository)
    {
        $this->eventMatchRepository = $eventMatchRepository;
    }

    /**
     * Undocumented function.
     *
     * @param  \App\Models\Event $event
     * @param  \App\DataTransferObjects\EventMatchData $eventMatchData
     *
     * @return \App\Models\EventMatch $cratedMatch
     */
    public function __invoke(Event $event, EventMatchData $eventMatchData)
    {
        /* @var \App\Models\EventMatch */
        $createdMatch = $this->eventMatchRepository->createForEvent($event, $eventMatchData);

        if ($eventMatchData->titles) {
            $eventMatchData->titles->map(
                fn (Title $title) => $this->eventMatchRepository->addTitleToMatch(
                    $createdMatch,
                    $title
                )
            );
        }

        $eventMatchData->referees->map(
            fn (Referee $referee) => $this->eventMatchRepository->addRefereeToMatch(
                $createdMatch,
                $referee
            )
        );

        $eventMatchData->competitors->each(function ($sideCompetitors, $sideNumber) use ($createdMatch) {
            if (array_key_exists('wrestlers', $sideCompetitors)) {
                foreach ($sideCompetitors['wrestlers'] as $wrestler) {
                    $this->eventMatchRepository->addWrestlerToMatch($createdMatch, $wrestler, $sideNumber);
                }
            } elseif (array_key_exists('wrestlers', $sideCompetitors)) {
                foreach ($sideCompetitors['tag_teams'] as $wrestler) {
                    $this->eventMatchRepository->addTagTeamToMatch($createdMatch, $wrestler, $sideNumber);
                }
            }
        });

        return $createdMatch;
    }
}
