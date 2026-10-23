<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Lifecycle\Events\EventDeletionEligibility;
use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Lifecycle\Tournaments\TournamentMatchRequirements;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Tournaments\Tournament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(private readonly DeletionStateManager $deletionState, private readonly TournamentLocks $tournamentLocks, private readonly TournamentMatchRequirements $tournamentRequirements) {}

    /**
     * Delete an event.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * MATCH IMPACT:
     * - Refuses to delete an event whose matches created or closed a title reign; the result must be corrected first
     * - Does not delete or change the matches or booking records; only the event is soft deleted
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
            $tournamentIds = $this->tournamentLocks->forEvent($event->id);

            if ($tournamentIds !== []) {
                $this->tournamentLocks->lockMany($tournamentIds, [$event->id], $event->date);
            }
            $lockedEvent = $event->refreshForUpdate();

            $this->tournamentRequirements->ensureEventDeletable($lockedEvent);
            EventDeletionEligibility::ensureCanBeDeleted($lockedEvent);
            Tournament::query()->whereKey($tournamentIds)->increment('version');
            EventPublication::query()->forEventId($lockedEvent->id)->update([
                'published_at' => null, 'results_published_at' => null, 'unpublished_at' => now(),
                'version' => DB::raw('version + 1'),
            ]);
            $this->deletionState->delete($lockedEvent, $deletionDate ?? now());
        });
    }
}
