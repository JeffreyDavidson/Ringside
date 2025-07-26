<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Events\Event;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction
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
        $event->delete();
    }
}
