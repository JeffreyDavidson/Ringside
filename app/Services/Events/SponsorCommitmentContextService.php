<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\Events\Event;
use App\Services\Promotions\PromotionTimeZoneService;

class SponsorCommitmentContextService
{
    /** @return array{name: string, date: ?string, venue_id: ?int, timezone: string, archived: bool, updated_at: ?string} */
    public function get(Event $event): array
    {
        $event->loadMissing('promotion');

        return ['name' => $event->name, 'date' => $event->date?->toIso8601String(), 'venue_id' => $event->venue_id,
            'timezone' => PromotionTimeZoneService::zoneOf($event->promotion), 'archived' => $event->trashed(), 'updated_at' => $event->updated_at?->toIso8601String()];
    }
}
