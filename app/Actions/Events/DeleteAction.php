<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Lifecycle\Periods\DeletionStateManager;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(private readonly DeletionStateManager $deletionState) {}

    /**
     * Delete an event.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * MATCH IMPACT:
     * - Cascades to associated matches and booking records
     * - Preserves match history for reporting and statistics
     * - No impact on wrestler/manager employment or career records
     *
     * VENUE IMPACT:
     * - Does not affect venue availability or booking
     * - Preserves venue-event relationship history
     * - Maintains venue statistics and historical data
     *
     * OTHER CLEANUP:
     * - Soft deletes the event record
     * - Allows for future restoration if needed
     * - Maintains referential integrity with historical data
     * - Preserves promotional and marketing data
     *
     * @param  Event  $event  The event to delete
     * @param  Carbon|null  $deletionDate  The deletion date (defaults to now)
     */
    public function handle(Event $event, ?Carbon $deletionDate = null): void
    {
        DB::transaction(function () use ($event, $deletionDate): void {
            $lockedEvent = $event->refreshForUpdate();

            EventPublication::query()->where('event_id', $lockedEvent->id)->update([
                'published_at' => null, 'results_published_at' => null, 'unpublished_at' => now(),
                'version' => DB::raw('version + 1'),
            ]);
            $this->deletionState->delete($lockedEvent, $deletionDate ?? now());
        });
    }
}
