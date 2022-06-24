<?php

declare(strict_types=1);

namespace App\Actions\EventMatches;

use App\Repositories\EventMatchRepository;

abstract class BaseEventMatchAction
{
    /**
     * The repository to save event matches.
     *
     * @var \App\Repositories\EventMatchRepository
     */
    protected EventMatchRepository $eventMatchRepository;

    /**
     * Create a new add match for event instance.
     *
     * @param \App\Repositories\EventMatchRepository $eventMatchRepository
     */
    public function __construct(EventMatchRepository $eventMatchRepository)
    {
        $this->eventMatchRepository = $eventMatchRepository;
    }
}
