<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventData;
use App\Models\Events\Event;
use App\Repositories\EventRepository;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseEventAction
{
    use AsAction;

    public function __construct(
        EventRepository $eventRepository
    ) {
        parent::__construct($eventRepository);
    }

    /**
     * Create an event.
     *
     * This handles the complete event creation workflow:
     * - Creates the event record with name, date, venue, and description
     * - Sets initial status based on whether a date is provided
     * - Establishes the event for future match booking and scheduling
     *
     * @param  EventData  $eventData  The data transfer object containing event information
     * @return Event The newly created event instance
     *
     * @example
     * ```php
     * // Create a scheduled event
     * $eventData = new EventData([
     *     'name' => 'WrestleMania 40',
     *     'date' => now()->addMonths(3),
     *     'venue_id' => 1,
     *     'preview' => 'The grandest stage of them all'
     * ]);
     * $event = CreateAction::run($eventData);
     *
     * // Create a draft event (no date yet)
     * $eventData = new EventData([
     *     'name' => 'Summer Slam TBD',
     *     'preview' => 'The biggest party of the summer'
     * ]);
     * $draftEvent = CreateAction::run($eventData);
     * ```
     */
    public function handle(EventData $eventData): Event
    {
        return DB::transaction(function () use ($eventData): Event {
            // Create the base event record
            $event = $this->eventRepository->create($eventData);

            return $event;
        });
    }
}
