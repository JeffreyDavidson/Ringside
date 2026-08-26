<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventData;
use App\Lifecycle\EventSchedulingEligibility;
use App\Models\Events\Event;
use App\Services\EventVenueSchedulingService;
use App\Services\MatchAssignmentConflictService;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __construct(
        private readonly MatchAssignmentConflictService $assignmentConflicts,
        private readonly EventVenueSchedulingService $venueScheduling,
    ) {}

    public function handle(Event $event, EventData $eventData): Event
    {
        return DB::transaction(function () use ($event, $eventData): Event {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            EventSchedulingEligibility::ensureDateCanChange($lockedEvent, $eventData->date);
            $venue = $this->venueScheduling->lockScheduledVenue($eventData->date, $eventData->venue);
            $this->venueScheduling->ensureAvailable($venue, $eventData->date, $lockedEvent);

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
}
