<?php

declare(strict_types=1);

namespace App\Actions\EventMatches;

use App\Repositories\EventMatchRepository;

abstract class BaseEventMatchAction
{
    /**
     * Create a new base event match action instance.
     */
    public function __construct(protected EventMatchRepository $eventMatchRepository)
    {
    }
}
