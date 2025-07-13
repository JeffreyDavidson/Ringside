<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction extends BaseTitleAction
{
    use AsAction;

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
     *
     * @example
     * ```php
     * // Delete a title immediately
     * $title = Title::find(1);
     * DeleteAction::run($title);
     *
     * // Delete with specific date
     * $title = Title::find(1);
     * DeleteAction::run($title, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Title $title, ?Carbon $deletionDate = null): void
    {
        $deletionDate = $this->getEffectiveDate($deletionDate);

        DB::transaction(function () use ($title, $deletionDate): void {
            // Handle title status cleanup based on current state
            if ($title->hasDebuted() && $title->isCurrentlyActive()) {
                // End active status (pull the title from active competition)
                $this->titleRepository->pull($title, $deletionDate);
            } elseif ($title->isRetired()) {
                // End retirement period (retired titles are not active)
                $this->titleRepository->endRetirement($title, $deletionDate);
            }
            // Note: Inactive (pulled) titles that have debuted require no status cleanup

            // Soft delete the title record
            $this->titleRepository->delete($title);
        });
    }
}
