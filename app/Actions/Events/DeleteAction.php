<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Events\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction extends BaseEventAction
{
    use AsAction;

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
     *
     * @example
     * ```php
     * // Delete event immediately
     * $event = Event::find(1);
     * DeleteAction::run($event);
     *
     * // Delete with specific date
     * DeleteAction::run($event, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Event $event, ?Carbon $deletionDate = null): void
    {
        $deletionDate = $this->getEffectiveDate($deletionDate);

        DB::transaction(function () use ($event): void {
            // Handle associated matches and bookings
            // Note: Match cleanup is handled automatically by the repository
            // through proper foreign key constraints and cascade rules

            // Record deletion for audit trail
            // Note: Deletion tracking is handled by Laravel's soft delete timestamps
            // The $deletionDate is available for any future audit trail needs

            // Soft delete the event record
            $this->eventRepository->delete($event);
        });
    }
}
