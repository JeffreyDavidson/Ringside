<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Events\Event;
use App\Services\EventDeletionService;
use Illuminate\Support\Carbon;

class DeleteAction
{
    public function __construct(private readonly EventDeletionService $deletion) {}

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
        $this->deletion->delete($event, $deletionDate ?? now());
    }
}
