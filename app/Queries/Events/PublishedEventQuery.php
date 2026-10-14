<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Data\Events\PublicEventData;
use App\Enums\Events\PublicAnnouncement;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Scopes\PromotionContextScope;

/**
 * @phpstan-import-type Card from EventPublication
 * @phpstan-import-type Results from EventPublication
 */
class PublishedEventQuery
{
    public function __construct(private readonly PublicEventSourceQuery $source) {}

    public function find(string $publicId, string $slug): EventPublication
    {
        $publication = EventPublication::query()->withoutGlobalScope(PromotionContextScope::class)->where('public_id', $publicId)->where('slug', $slug)->whereNotNull('published_at')->firstOrFail();
        $event = Event::query()->withoutGlobalScope(PromotionContextScope::class)->whereKey($publication->event_id)->where('promotion_id', $publication->promotion_id)->firstOrFail();
        $publication->setRelation('event', $event);

        return $publication;
    }

    public function page(EventPublication $publication, bool $results = false): PublicEventData
    {
        abort_if($publication->card === null, 404);
        abort_if($results && $publication->results_published_at === null, 404);
        $source = $this->source->get($publication->event);
        $scheduleChanged = $publication->schedule_fingerprint !== $source['schedule'];
        $resultsChanged = $publication->results_fingerprint !== $this->source->fingerprint($this->source->resultSource($source, $publication->results_source ?? []));
        $card = $publication->card;
        $notice = PublicAnnouncement::from($card['announcement'])->isNotice();
        $params = ['publicId' => $publication->public_id, 'slug' => $publication->slug];

        return $this->project($card, route('shows.show', $params),
            ! $scheduleChanged && ! $notice && ! $resultsChanged && $publication->results_published_at !== null ? route('shows.results', $params) : null,
            $scheduleChanged || $card['poster'] === null ? null : route('shows.poster', [...$params, 'asset' => $card['poster']['key']]),
            $results, $scheduleChanged, $resultsChanged, $results ? $publication->results : null);
    }

    public function preview(Event $event, EventPublication $publication): PublicEventData
    {
        $card = $this->source->card($this->source->get($event), $publication->draft);

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
