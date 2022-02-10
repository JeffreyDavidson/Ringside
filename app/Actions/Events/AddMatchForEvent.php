<?php

namespace App\Actions\Events;

use App\DataTransferObjects\EventMatchData;
use App\Models\Event;
use App\Models\Referee;
use App\Models\Title;
use App\Repositories\EventMatchRepository;

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
        /** @var \App\Models\EventMatch $createdMatch */
        $createdMatch = $this->eventMatchRepository->createForEvent($event, $eventMatchData);

        $eventMatchData->referees->whenNotEmpty(function ($referees) use ($createdMatch) {
            $this->addRefereesToMatch($createdMatch, $referees);
        });

        $eventMatchData->titles->whenNotEmpty(function ($titles) use ($createdMatch) {
            $this->addTitlesToMatch($createdMatch, $titles);
        });

        $this->addCompetitorsToMatch($createdMatch, $eventMatchData->competitors);

        return $createdMatch;
    }

    /**
     * Add titles to an event match.
     *
     * @param \App\Models\EventMatch $createdMatch
     * @param \Illuminate\Database\Eloquent\Collection $titles
     *
     * @return void
     */
    private function addTitlesToMatch($eventMatch, $titles)
    {
        $titles->map(
            fn (Title $title) => $this->eventMatchRepository->addTitleToMatch($eventMatch, $title)
        );
    }

    /**
     * Add referees to an event match.
     *
     * @param \App\Models\EventMatch $createdMatch
     * @param \Illuminate\Database\Eloquent\Collection $referees
     *
     * @return void
     */
    private function addRefereesToMatch($eventMatch, $referees)
    {
        $referees->map(
            fn (Referee $referee) => $this->eventMatchRepository->addRefereeToMatch($eventMatch, $referee)
        );
    }

    /**
     * Add competitors to an event match.
     *
     * @param \App\Models\EventMatch $createdMatch
     * @param \Illuminate\Database\Eloquent\Collection $competitors
     *
     * @return void
     */
    private function addCompetitorsToMatch($eventMatch, $competitors)
    {
        $competitors->each(function ($sideCompetitors, $sideNumber) use ($eventMatch) {
            if ($sideCompetitors->has('wrestlers')) {
                $sideCompetitors->get('wrestlers')->each(
                    fn ($wrestler) => $this->eventMatchRepository->addWrestlerToMatch(
                        $eventMatch,
                        $wrestler,
                        $sideNumber
                    )
                );
            } elseif ($sideCompetitors->has('tag_teams')) {
                $sideCompetitors->get('tag_teams')->each(
                    fn ($tagTeam) => $this->eventMatchRepository->addTagTeamToMatch(
                        $eventMatch,
                        $tagTeam,
                        $sideNumber
                    )
                );
            }
        });
    }
}
