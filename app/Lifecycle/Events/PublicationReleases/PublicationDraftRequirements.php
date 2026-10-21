<?php

declare(strict_types=1);

namespace App\Lifecycle\Events\PublicationReleases;

use App\Enums\Events\PublicAnnouncement;
use App\Exceptions\Events\EventPublicationException;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Services\Events\PublicEventSourceService;
use Illuminate\Support\Facades\Storage;

/**
 * The checks every section release makes on the saved draft: the publication exists, the announced matches are still
 * on the card, the event has a date (or the draft is a notice for a future event), and the poster file is still stored.
 *
 * @phpstan-import-type Source from PublicEventSourceService
 */
final readonly class PublicationDraftRequirements
{
    /**
     * @param  Source  $source
     *
     * @throws EventPublicationException when the draft cannot be released
     */
    public function ensureDraftReleasable(EventPublication $publication, Event $event, array $source): PublicAnnouncement
    {
        if (! $publication->exists) {
            throw EventPublicationException::invalid();
        }
        $draft = $publication->draft;
        $announcement = PublicAnnouncement::from($draft['announcement']);
        $selected = array_column($draft['matches'], 'id');
        $available = array_column($source['matches'], 'id');
        if (array_diff($selected, $available) !== [] || ($event->date === null && ! $announcement->isNotice())
            || ($announcement->isNotice() && ($event->date?->isPast() || ! $draft['notice']))) {
            throw EventPublicationException::invalid();
        }
        if ($draft['poster'] !== null && ! Storage::disk('local')->exists('event-posters/'.$draft['poster']['key'])) {
            throw EventPublicationException::invalid();
        }

        return $announcement;
    }
}
