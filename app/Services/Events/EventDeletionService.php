<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\VenueSchedulingEligibility;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EventDeletionService
{
    public function __construct(private readonly DeletionStateManager $deletionState) {}

    public function delete(Event $event, Carbon $deletionDate): void
    {
        DB::transaction(function () use ($event, $deletionDate): void {
            $lockedEvent = Event::query()->withTrashed()->whereKey($event->getKey())->lockForUpdate()->firstOrFail();
            $this->deletionState->delete($lockedEvent, $deletionDate);
        });
    }

    public function restore(Event $event, Carbon $restoreDate): void
    {
        DB::transaction(function () use ($event, $restoreDate): void {
            $lockedEvent = Event::query()->withTrashed()->whereKey($event->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedEvent->date !== null && $lockedEvent->venue_id !== null) {
                $venue = Venue::query()->whereKey($lockedEvent->venue_id)->lockForUpdate()->firstOrFail();
                VenueSchedulingEligibility::ensureAvailable($venue, $lockedEvent->date, $lockedEvent);
            }

            $this->deletionState->restore($lockedEvent, $restoreDate);
        });
    }
}
