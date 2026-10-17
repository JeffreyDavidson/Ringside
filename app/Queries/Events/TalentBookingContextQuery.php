<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Models\Events\Event;
use App\Services\Promotions\PromotionTimeZoneService;

class TalentBookingContextQuery
{
    /** @return array<string, mixed> */
    public function get(Event $event): array
    {
        $event->load('promotion');
        $venue = $event->venue()->withTrashed()->first();

        return ['event_name' => $event->name, 'event_date' => $event->date?->utc()->toIso8601String(),
            'timezone' => PromotionTimeZoneService::zoneOf($event->promotion), 'venue_id' => $event->venue_id,
            'venue_name' => $venue?->name, 'venue_address' => $venue === null ? null : [$venue->street_address, $venue->city, $venue->state, $venue->zipcode],
            'venue_deleted' => $event->venue_id !== null && ($venue === null || $venue->trashed()),
            'deletion_version' => $event->lifecycleTransitions()->where('dimension', LifecycleDimension::Deletion)->orderByDesc('id')->first()->id ?? 0];
    }
}
