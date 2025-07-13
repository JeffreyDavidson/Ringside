<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction extends BaseStableAction
{
    use AsAction;

    /**
     * Delete a stable.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * MEMBERSHIP IMPACT:
     * - Ends all current wrestler memberships (wrestlers become available as singles)
     * - Ends all current tag team memberships (tag teams continue independently)
     * - Manager associations automatically end when wrestler/tag team memberships end
     * - Preserves membership history for reporting and record-keeping
     * - No impact on individual member careers (they continue independently)
     *
     * STATUS IMPACT:
     * - Ends stable debut period if active
     * - Does not affect individual member status (they maintain their employment/retirement status)
     * - Preserves stable debut history for administrative records
     *
     * OTHER CLEANUP:
     * - Soft deletes the stable record
     * - Allows for future restoration if needed
     * - Maintains referential integrity with historical data
     *
     * @param  Stable  $stable  The stable to delete
     * @param  Carbon|null  $deletionDate  The deletion date (defaults to now)
     *
     * @example
     * ```php
     * // Delete stable immediately
     * $stable = Stable::find(1);
     * DeleteAction::run($stable);
     *
     * // Delete with specific date
     * DeleteAction::run($stable, Carbon::parse('2024-12-31'));
     *
     * // Delete The Ministry stable
     * $ministry = Stable::where('name', 'The Ministry')->first();
     * DeleteAction::run($ministry, Carbon::parse('2024-06-30'));
     * ```
     */
    public function handle(Stable $stable, ?Carbon $deletionDate = null): void
    {
        $deletionDate = $this->getEffectiveDate($deletionDate);

        DB::transaction(function () use ($stable, $deletionDate): void {
            // Handle stable status - debuted stables need debut period ended
            if ($stable->hasDebuted()) {
                $this->stableRepository->endActivity($stable, $deletionDate);
            }

            // End current wrestler memberships (wrestlers continue as singles)
            $this->stableRepository->removeWrestlers($stable, $stable->currentWrestlers, $deletionDate);

            // End current tag team memberships (tag teams continue independently)
            $this->stableRepository->removeTagTeams($stable, $stable->currentTagTeams, $deletionDate);

            // Manager associations automatically end when wrestler/tag team memberships end
            // No direct manager removal needed since managers are associated through wrestlers/tag teams

            // Soft delete the stable record
            $this->stableRepository->delete($stable);
        });
    }
}
