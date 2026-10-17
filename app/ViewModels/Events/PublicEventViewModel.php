<?php

declare(strict_types=1);

namespace App\ViewModels\Events;

use App\Data\Events\PublicEventData;
use App\Enums\Events\PublicAnnouncement;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Queries\Events\PublicEventPreviewCardQuery;
use App\Queries\Events\PublishedEventChangesQuery;

/**
 * Builds the public event page from a publication's released card, hiding what the event's current schedule or results have outdated.
 *
 * @phpstan-import-type Card from EventPublication
 * @phpstan-import-type Results from EventPublication
 */
class PublicEventViewModel
{
    public function __construct(private readonly PublishedEventChangesQuery $changes, private readonly PublicEventPreviewCardQuery $previewCard) {}

    /**
     * The caller has checked that the publication has a released card (and released results when $results is true).
     *
     * @param  Card  $card  The publication's released card
     */
    public function page(EventPublication $publication, array $card, bool $results = false): PublicEventData
    {
        $changes = $this->changes->get($publication);
        $scheduleChanged = $changes['scheduleChanged'];
        $resultsChanged = $changes['resultsChanged'];
        $notice = PublicAnnouncement::from($card['announcement'])->isNotice();
        $params = ['publicId' => $publication->public_id, 'slug' => $publication->slug];

        return $this->project($card, route('shows.show', $params),
            ! $scheduleChanged && ! $notice && ! $resultsChanged && $publication->results_published_at !== null ? route('shows.results', $params) : null,
            $scheduleChanged || $card['poster'] === null ? null : route('shows.poster', [...$params, 'asset' => $card['poster']['key']]),
            $results, $scheduleChanged, $resultsChanged, $results ? $publication->results : null);
    }

    public function preview(Event $event, EventPublication $publication): PublicEventData
    {
        $card = $this->previewCard->get($event, $publication);

        return $this->project($card, route('shows.show', ['publicId' => $publication->public_id, 'slug' => $publication->slug]), null,
            $card['poster'] === null ? null : route('events.public-pages.poster', $event), preview: true);
    }

    /**
     * @param  Card  $card
     * @param  Results|null  $releasedResults
     */
    private function project(array $card, string $cardUrl, ?string $resultsUrl, ?string $posterUrl, bool $results = false, bool $scheduleChanged = false, bool $resultsChanged = false, ?array $releasedResults = null, bool $preview = false): PublicEventData
    {
        $hidden = $scheduleChanged;

        return new PublicEventData(title: $card['title'], promotion: $hidden ? '' : $card['promotion'], announcement: $hidden ? '' : $card['announcement'],
            summary: $hidden ? null : $card['summary'], notice: $hidden ? null : $card['notice'], date: $hidden ? null : $card['date'], timezone: $card['timezone'],
            venue: $hidden ? null : $card['venue'], address: $hidden ? null : $card['address'], doors: $hidden ? null : $card['doors'], tickets: $hidden ? null : $card['tickets'],
            posterUrl: $posterUrl, posterAlt: $card['poster']['alt'] ?? null, posterCaption: $hidden ? null : ($card['poster']['caption'] ?? null),
            rows: $hidden || ($results && $resultsChanged) ? [] : ($results ? ($releasedResults['rows'] ?? []) : $card['rows']),
            cardUrl: $cardUrl, resultsUrl: $resultsUrl, isResults: $results, isPreview: $preview, scheduleChanged: $scheduleChanged, resultsChanged: $results && $resultsChanged,
            correctedAt: $hidden || $resultsChanged ? null : ($releasedResults['corrected_at'] ?? null));
    }
}
