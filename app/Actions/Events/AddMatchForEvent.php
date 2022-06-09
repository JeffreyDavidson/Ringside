<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\EventMatchData;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use App\Repositories\EventMatchRepository;
use Illuminate\Support\Collection;

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
     * @param  \App\Repositories\EventMatchRepository  $eventMatchRepository
     */
    public function __construct(EventMatchRepository $eventMatchRepository)
    {
        $this->eventMatchRepository = $eventMatchRepository;
    }

    /**
     * Undocumented function.
     *
     * @param  \App\Models\Event  $event
     * @param  \App\Data\EventMatchData  $eventMatchData
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
     * @param  \App\Models\EventMatch  $eventMatch
     * @param  \Illuminate\Database\Eloquent\Collection<Title>  $titles
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
     * @param  \App\Models\EventMatch  $eventMatch
     * @param  \Illuminate\Database\Eloquent\Collection<Referee>  $referees
     * @return void
     */
    private function addRefereesToMatch($eventMatch, $referees): void
    {
        $referees->map(
            fn (Referee $referee) => $this->eventMatchRepository->addRefereeToMatch($eventMatch, $referee)
        );
    }

    private function addCompetitorsToMatch(EventMatch $eventMatch, Collection $competitors): void
    {
        $competitors->each(function ($sideCompetitors, $sideNumber) use ($eventMatch) {
            if ($sideCompetitors->has('wrestlers')) {
                $this->addWrestlersToMatch($eventMatch, $sideCompetitors->get('wrestlers'), $sideNumber);
            }

            if ($sideCompetitors->has('tag_teams')) {
                $this->addTagTeamsToMatch($eventMatch, $sideCompetitors->get('tag_teams'), $sideNumber);
            }
        });
    }

    private function addWrestlersToMatch(EventMatch $eventMatch, Collection $wrestlers, int $sideNumber): void
    {
        $wrestlers->each(
            fn (Wrestler $wrestler) => $this->eventMatchRepository->addWrestlerToMatch(
                $eventMatch,
                $wrestler,
                $sideNumber
            )
        );
    }

    private function addTagTeamsToMatch($eventMatch, $tagTeams, $sideNumber): void
    {
        $tagTeams->each(
            fn (TagTeam $tagTeam) => $this->eventMatchRepository->addTagTeamToMatch(
                $eventMatch,
                $tagTeam,
                $sideNumber
            )
        );
    }
}
