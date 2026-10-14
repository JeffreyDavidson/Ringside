<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Events\Event;
use App\Models\Promotions\Promotion;

class SponsorCommitmentContextQuery
{
    /** @return array{name: string, date: ?string, venue_id: ?int, timezone: string, archived: bool, updated_at: ?string} */
    public function get(Event $event): array
    {
        $event->loadMissing('promotion');

        return ['name' => $event->name, 'date' => $event->date?->toIso8601String(), 'venue_id' => $event->venue_id,
            'timezone' => Promotion::zoneOf($event->promotion), 'archived' => $event->trashed(), 'updated_at' => $event->updated_at?->toIso8601String()];
    }
}
