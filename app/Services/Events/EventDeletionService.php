<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Lifecycle\DeletionStateManager;
use App\Models\Events\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EventDeletionService
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly EventVenueSchedulingService $venueScheduling,
    ) {}

    public function delete(Event $event, Carbon $deletionDate): void
    {
        DB::transaction(function () use ($event, $deletionDate): void {
            $lockedEvent = $event->refreshForUpdate();
            $this->deletionState->delete($lockedEvent, $deletionDate);
        });
    }

    public function restore(Event $event, Carbon $restoreDate): void
    {
        DB::transaction(function () use ($event, $restoreDate): void {
            $lockedEvent = $event->refreshForUpdate();

            $this->venueScheduling->schedule($lockedEvent->date, $lockedEvent->venue, $lockedEvent);

            $this->deletionState->restore($lockedEvent, $restoreDate);
        });
    }
}
