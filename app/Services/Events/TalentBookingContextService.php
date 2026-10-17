<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Queries\Events\EventDeletionVersionQuery;
use App\Queries\Events\EventVenueQuery;
use App\Services\Promotions\PromotionTimeZoneService;

/**
 * The event details a talent agreement depends on. A confirmed agreement stores this array, so its keys and order
 * must not change.
 *
 * @phpstan-type Context array{event_name: string, event_date: ?string, timezone: string, venue_id: ?int, venue_name: ?string, venue_address: ?list<?string>, venue_deleted: bool, deletion_version: int}
 */
class TalentBookingContextService
{
    public function __construct(private readonly EventVenueQuery $venues, private readonly EventDeletionVersionQuery $deletions) {}

    /** @return Context */
    public function get(Event $event): array
    {
        $event->load('promotion');
        $venue = $this->venues->get($event);

        return ['event_name' => $event->name, 'event_date' => $event->date?->utc()->toIso8601String(),
            'timezone' => PromotionTimeZoneService::zoneOf($event->promotion), 'venue_id' => $event->venue_id,
            'venue_name' => $venue?->name, 'venue_address' => $venue instanceof Venue ? [$venue->street_address, $venue->city, $venue->state, $venue->zipcode] : null,
            'venue_deleted' => $event->venue_id !== null && (! $venue instanceof Venue || $venue->trashed()),
            'deletion_version' => $this->deletions->get($event)];
    }
}
