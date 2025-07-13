<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\Events\EventData;
use App\Models\Events\Event;

interface EventRepositoryInterface
{
    // CRUD operations
    public function create(EventData $eventData): Event;

    public function update(Event $event, EventData $eventData): Event;

    public function delete(Event $event): void;

    public function restore(Event $event): void;
}
