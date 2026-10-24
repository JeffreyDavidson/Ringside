<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Events\NameTakenException;
use App\Exceptions\Events\VenueDeletedException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Lifecycle\Venues\VenueSchedulingEligibility;
use App\Models\Events\Event;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    public function __construct(private readonly RecordNameLock $nameLock) {}

    /**
     * Create an event.
     *
     * Rejects a name another event of the promotion already uses (deleted ones count, as they do for the form rule).
     * Nothing in the database keeps it unique, so the name lock is the first lock of the transaction, before the
     * venue row lock, and the check that follows sees the other transaction's committed event.
     *
     * @param  int|null  $promotionId  The promotion the event belongs to: the enforced promotion for a member, null for an administrator without one
     *
     * @throws NameTakenException When another event of the promotion already has the name
     * @throws VenueDeletedException When the venue is deleted
     */
    public function handle(EventData $eventData, ?int $promotionId): Event
    {
        return DB::transaction(function () use ($eventData, $promotionId): Event {
            $name = mb_trim($eventData->name);

            $this->nameLock->lock(GuardedName::EventName, $promotionId, $name);

            $nameTaken = Event::query()
                ->withoutGlobalScope(PromotionContextScope::class)
                ->withTrashed()
                ->whereNameInPromotion($name, $promotionId)
                ->exists();

            if ($nameTaken) {
                throw NameTakenException::name($name);
            }

            $venue = $eventData->venue?->refreshForUpdate();

            if ($venue !== null && $venue->trashed()) {
                throw VenueDeletedException::cannotBeChosen($venue);
            }

            if ($venue !== null && $eventData->date instanceof Carbon) {
                VenueSchedulingEligibility::ensureAvailable($venue, $eventData->date);
            }

            $event = Event::query()
                ->make([
                    'name' => $name,
                    'date' => $eventData->date,
                    'venue_id' => $eventData->venue?->id,
                    'preview' => $eventData->preview,
                ])
                ->forceFill(['promotion_id' => $promotionId]);
            $event->save();

            return $event;
        }, attempts: 3);
    }
}
