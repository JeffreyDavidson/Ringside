<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Repositories\EventRepository;

abstract class BaseEventAction
{
    /**
     * Create a new base event action instance.
     */
    public function __construct(protected EventRepository $eventRepository)
    {
    }
}
