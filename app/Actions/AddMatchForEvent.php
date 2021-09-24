<?php

namespace App\Actions;

use App\Models\Event;
use App\Repositories\EventMatchRepository;

class AddMatchForEvent
{
    private ?EventMatchRepository $eventMatchRepository = null;

    public function __construct(EventMatchRepository $eventMatchRepository)
    {
        $this->eventMatchRepository = $eventMatchRepository;
    }

    public function __invoke(Event $event, array $data)
    {
        $createdMatch = $this->eventMatchRepository->createForEvent($event, $data);

        if (count($data['titles'])) {
            foreach ($data['titles'] as $titleId) {
                $this->eventMatchRepository->addTitleToMatch($createdMatch, $titleId);
            }
        }

        foreach ($data['referees'] as $refereeId) {
            $this->eventMatchRepository->addRefereeToMatch($createdMatch, $refereeId);
        }

        foreach ($data['competitors'] as $competitorId) {
            $this->eventMatchRepository->addCompetitorToMatch($createdMatch, $competitorId);
        }

        return $createdMatch;
    }
}
