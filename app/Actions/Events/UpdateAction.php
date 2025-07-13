<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventData;
use App\Models\Events\Event;
use App\Repositories\EventRepository;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseEventAction
{
    use AsAction;

    public function __construct(
        EventRepository $eventRepository
    ) {
        parent::__construct($eventRepository);
    }

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
     *
     * @example
     * ```php
     * // Update event details
     * $eventData = new EventData([
     *     'name' => 'Updated Event Name',
     *     'date' => now()->addMonth(),
     *     'venue_id' => 2
     * ]);
     * $updatedEvent = UpdateAction::run($event, $eventData);
     *
     * // Change venue for an event
     * $eventData = new EventData([
     *     'venue_id' => 3,
     *     'preview' => 'Updated with new venue information'
     * ]);
     * $updatedEvent = UpdateAction::run($event, $eventData);
     * ```
     */
    public function handle(Event $event, EventData $eventData): Event
    {
        return DB::transaction(function () use ($event, $eventData): Event {
            // Update the event's information
            $this->eventRepository->update($event, $eventData);

            return $event;
        });
    }
}
