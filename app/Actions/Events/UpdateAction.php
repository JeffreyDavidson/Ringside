<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventData;
use App\Lifecycle\Events\EventSchedulingEligibility;
use App\Lifecycle\Venues\VenueSchedulingEligibility;
use App\Models\Events\Event;
use App\Services\Matches\MatchAssignmentConflictService;
use App\Services\Matches\SchedulingSlotLockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __construct(
        private readonly MatchAssignmentConflictService $assignmentConflicts,
        private readonly SchedulingSlotLockService $slotLockService,
    ) {}

    public function handle(Event $event, EventData $eventData): Event
    {
        return DB::transaction(function () use ($event, $eventData): Event {
            // Slot locks come before every event row lock: an empty target slot has no row to lock, so this is
            // the only thing that makes two reschedules into it queue. The old slot is locked too so that
            // events swapping dates queue instead of deadlocking on each other's rows. The date comes from
            // the caller's model because the row cannot be locked yet, so a concurrent change of this very
            // event's date between loading it and this transaction can leave a slot unlocked.
            if (EventSchedulingEligibility::isDateChanging($event, $eventData->date)) {
                $this->slotLockService->lock($event->date, $eventData->date);
            }

            $lockedEvent = $event->refreshForUpdate();

            EventSchedulingEligibility::ensureDateCanChange($lockedEvent, $eventData->date);
            $venue = $eventData->venue?->refreshForUpdate();

            if ($venue !== null && $eventData->date instanceof Carbon) {
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
}
