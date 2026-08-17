<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Lifecycle\ChampionshipReignManager;
use App\Lifecycle\DeletionStateManager;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(
        private readonly ChampionshipReignManager $championshipReigns,
        private readonly DeletionStateManager $deletionState,
    ) {}

    /**
     * Delete a title.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * CHAMPIONSHIP IMPACT:
     * - Ends any active championship reigns
     * - Preserves championship history for reporting
     * - No impact on past championship records or statistics
     *
     * STATUS IMPACT:
     * - Ends active/debut status if currently active
     * - Ends retirement if currently retired
     * - Preserves status history for administrative records
     *
     * OTHER CLEANUP:
     * - Soft deletes the title record
     * - Allows for future restoration if needed
     * - Maintains referential integrity with historical data
     *
     * @param  Title  $title  The title to delete
     * @param  Carbon|null  $deletionDate  The deletion date (defaults to now)
     */
    public function handle(Title $title, ?Carbon $deletionDate = null): void
    {
        $deletionDate = $deletionDate ?? now();

        DB::transaction(function () use ($title, $deletionDate): void {
            $lockedTitle = Title::query()
                ->whereKey($title->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Handle title status cleanup based on current state
            if ($lockedTitle->isCurrentlyActive()) {
                // End active status (pull the title from active competition)
                $lockedTitle->activityPeriods()->where('ended_at', null)->update(['ended_at' => $deletionDate]);
            } elseif ($lockedTitle->isRetired()) {
                // End retirement period (retired titles are not active)
                $lockedTitle->retirements()->where('ended_at', null)->update(['ended_at' => $deletionDate]);
            }
            // Note: Inactive (pulled) titles that have debuted require no status cleanup

            $this->championshipReigns->endCurrentReign($lockedTitle, $deletionDate);

            // Soft delete the title record
            $this->deletionState->delete($lockedTitle, $deletionDate);
        });
    }
}
