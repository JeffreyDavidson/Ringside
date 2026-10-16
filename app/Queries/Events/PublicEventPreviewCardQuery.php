<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Events\Event;
use App\Models\Events\EventPublication;

/** @phpstan-import-type Card from EventPublication */
class PublicEventPreviewCardQuery
{
    public function __construct(private readonly PublicEventSourceQuery $source) {}

    /** @return Card */
    public function get(Event $event, EventPublication $publication): array
    {
        return $this->source->card($this->source->get($event), $publication->draft);
    }
}
