<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Events\NameTakenException;
use App\Lifecycle\Events\EventSchedulingEligibility;
use App\Lifecycle\Events\SchedulingSlotLock;
use App\Lifecycle\Naming\RecordNameLock;
use App\Lifecycle\Venues\VenueSchedulingEligibility;
use App\Models\Events\Event;
use App\Models\Scopes\PromotionContextScope;
use App\Services\Matches\MatchAssignmentConflictService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __construct(
        private readonly MatchAssignmentConflictService $assignmentConflicts,
        private readonly SchedulingSlotLock $slotLock,
        private readonly RecordNameLock $nameLock,
    ) {}

    /**
     * Update an event.
     *
     * Rejects a name another event of the promotion already uses (deleted ones count, as they do for the form rule).
     * Nothing in the database keeps it unique, so the name lock is the first lock of the transaction, before the
     * date-slot locks and the event's own row lock.
     *
     * @throws NameTakenException When another event of the promotion already has the name
     */
    public function handle(Event $event, EventData $eventData): Event
    {
        return DB::transaction(function () use ($event, $eventData): Event {
            $name = mb_trim($eventData->name);

            $this->nameLock->lock(GuardedName::EventName, $event->promotion_id, $name);

            // Slot locks come before every event row lock: an empty target slot has no row to lock, so this is
            // the only thing that makes two reschedules into it queue. The old slot is locked too so that
            // events swapping dates queue instead of deadlocking on each other's rows. The date comes from
            // the caller's model because the row cannot be locked yet, so a concurrent change of this very
            // event's date between loading it and this transaction can leave a slot unlocked.
            if (EventSchedulingEligibility::isDateChanging($event, $eventData->date)) {
                $this->slotLock->lock($event->date, $eventData->date);
            }

            $lockedEvent = $event->refreshForUpdate();

            $nameTaken = Event::query()
                ->withoutGlobalScope(PromotionContextScope::class)
                ->withTrashed()
                ->whereNameInPromotion($name, $lockedEvent->promotion_id)
                ->whereKeyNot($lockedEvent->getKey())
                ->exists();

            if ($nameTaken) {
                throw NameTakenException::name($name);
            }

            EventSchedulingEligibility::ensureDateCanChange($lockedEvent, $eventData->date);
            $venue = $eventData->venue?->refreshForUpdate();

            if (
                $venue !== null
                && $eventData->date instanceof Carbon
                && VenueSchedulingEligibility::isBookingChanging($lockedEvent, $venue, $eventData->date)
            ) {
                VenueSchedulingEligibility::ensureAvailable($venue, $eventData->date, $lockedEvent);
            }

            if (EventSchedulingEligibility::isDateChanging($lockedEvent, $eventData->date)) {
                $this->assignmentConflicts->ensureEventCanBeRescheduled($lockedEvent, $eventData->date);
            }

            $lockedEvent->update([
                'name' => $name,
                'date' => $eventData->date,
                'venue_id' => $eventData->venue?->id,
                'preview' => $eventData->preview,
            ]);

            return $lockedEvent;
        }, attempts: 3);
    }
}
