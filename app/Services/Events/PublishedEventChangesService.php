<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\Events\EventPublication;

/**
 * Whether the event's schedule, released card or released results changed after the publication was released.
 *
 * @phpstan-import-type Source from PublicEventSourceService
 *
 * @phpstan-type Changes array{scheduleChanged: bool, cardChanged: bool, resultsChanged: bool}
 */
class PublishedEventChangesService
{
    public function __construct(private readonly PublicEventSourceService $source, private readonly PublicEventCardService $cards) {}

    /** @return Changes */
    public function get(EventPublication $publication): array
    {
        return $this->compare($publication, $this->source->get($publication->event));
    }

    /**
     * Compares a publication with a source the caller has already read.
     *
     * @param  Source  $source
     * @return Changes
     */
    public function compare(EventPublication $publication, array $source): array
    {
        return [
            'scheduleChanged' => $publication->schedule_fingerprint !== $source['schedule'],
            'cardChanged' => $publication->card_fingerprint !== $this->cards->cardFingerprint($source, $publication->card_source ?? []),
            'resultsChanged' => $publication->results_fingerprint !== $this->cards->fingerprint($this->cards->resultSource($source, $publication->results_source ?? [])),
        ];
    }
}
