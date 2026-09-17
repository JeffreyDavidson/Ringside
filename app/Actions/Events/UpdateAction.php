<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventData;
use App\Lifecycle\Events\EventSchedulingEligibility;
use App\Lifecycle\Venues\VenueSchedulingEligibility;
use App\Models\Events\Event;
use App\Services\Matches\MatchAssignmentConflictService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __construct(
        private readonly MatchAssignmentConflictService $assignmentConflicts,
    ) {}

    public function handle(Event $event, EventData $eventData): Event
    {
        return DB::transaction(function () use ($event, $eventData): Event {
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
