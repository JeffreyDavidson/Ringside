<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Events\EventPublication;

/** Whether the event's schedule or results changed after the publication was released. */
class PublishedEventChangesQuery
{
    public function __construct(private readonly PublicEventSourceQuery $source) {}

    /** @return array{scheduleChanged: bool, resultsChanged: bool} */
    public function get(EventPublication $publication): array
    {
        $source = $this->source->get($publication->event);

        return [
            'scheduleChanged' => $publication->schedule_fingerprint !== $source['schedule'],
            'resultsChanged' => $publication->results_fingerprint !== $this->source->fingerprint($this->source->resultSource($source, $publication->results_source ?? [])),
        ];
    }
}
