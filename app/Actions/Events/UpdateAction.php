<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventData;
use App\Lifecycle\EventSchedulingEligibility;
use App\Lifecycle\VenueSchedulingEligibility;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Services\MatchAssignmentConflictService;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __construct(private readonly MatchAssignmentConflictService $assignmentConflicts) {}

    /**
     * Update an event.
     *
     * This handles the complete event update workflow:
     * - Updates event information (name, date, venue, description)
     * - Maintains match integrity for existing bookings
     * - Preserves event history and existing match associations
     * - Updates event status based on new date information
     *
     * @param  Event  $event  The event to update
     * @param  EventData  $eventData  The updated event information
     * @return Event The updated event instance
     */
    public function handle(Event $event, EventData $eventData): Event
    {
        return DB::transaction(function () use ($event, $eventData): Event {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            EventSchedulingEligibility::ensureDateCanChange($lockedEvent, $eventData->date);
            $venue = $this->lockScheduledVenue($eventData);

            if ($venue !== null && $eventData->date !== null) {
                VenueSchedulingEligibility::ensureAvailable($venue, $eventData->date, $lockedEvent);
            }

            if (EventSchedulingEligibility::isDateChanging($lockedEvent, $eventData->date)) {
                $this->assignmentConflicts->ensureEventCanBeRescheduled($lockedEvent, $eventData->date);
            }

            $lockedEvent->update([
                'name' => $eventData->name,
                'date' => $eventData->date,
                'venue_id' => $eventData->venue?->id,
                'preview' => $eventData->preview,
            ]);

            return $lockedEvent;
        }, attempts: 3);
    }

    private function lockScheduledVenue(EventData $eventData): ?Venue
    {
        if ($eventData->date === null || $eventData->venue === null) {
            return null;
        }

        return Venue::query()
            ->whereKey($eventData->venue->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
