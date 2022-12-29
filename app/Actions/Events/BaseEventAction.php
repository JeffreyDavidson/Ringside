<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Repositories\EventRepository;

abstract class BaseEventAction
{
    public function __construct(protected EventRepository $eventRepository)
    {
    }
}
