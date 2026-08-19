<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventData;
use App\Models\Events\Event;
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

            if ($this->dateIsChanging($lockedEvent, $eventData)) {
                $this->assignmentConflicts->ensureEventCanBeRescheduled($lockedEvent, $eventData->date);
            }

            $lockedEvent->update([
                'name' => $eventData->name,
                'date' => $eventData->date,
                'venue_id' => $eventData->venue->id ?? null,
                'preview' => $eventData->preview,
            ]);

            return $lockedEvent;
        }, attempts: 3);
    }

    private function dateIsChanging(Event $event, EventData $data): bool
    {
        if ($event->date === null) {
            return $data->date !== null;
        }

        return $data->date === null || ! $event->date->equalTo($data->date);
    }
}
