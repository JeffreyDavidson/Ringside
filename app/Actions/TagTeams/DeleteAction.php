<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction
{
    use AsAction;

    /**
     * Delete a tag team.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * PARTNERSHIP IMPACT:
     * - Ends all current wrestler partnerships (wrestlers become singles competitors)
     * - Ends all current manager relationships
     * - Preserves partnership history for reporting
     * - No impact on individual wrestler/manager employment status
     *
     * EMPLOYMENT IMPACT:
     * - Uses StatusTransitionPipeline to properly release employed tag teams
     * - Automatically handles suspension ending through pipeline
     * - Retired tag teams remain retired (no artificial status changes)
     * - Does not affect individual member employment (they continue careers)
     * - Preserves tag team employment history for administrative records
     *
     * CHAMPIONSHIP IMPACT:
     * - Vacates any tag team championships held
     * - Preserves championship history and lineage
     *
     * OTHER CLEANUP:
     * - Soft deletes the tag team record
     * - Allows for future restoration if needed
     * - Maintains referential integrity with historical data
     *
     * NOTE ON DELETION TYPE:
     * This performs soft deletion only - the tag team record is marked as deleted
     * but preserved for historical reporting, championship lineage, and administrative purposes.
     *
     * @param  TagTeam  $tagTeam  The tag team to delete
     * @param  Carbon|null  $deletionDate  The deletion date (defaults to now)
     *
     * @example
     * ```php
     * // Delete tag team immediately
     * $tagTeam = TagTeam::where('name', 'The Hardys')->first();
     * DeleteAction::run($tagTeam);
     *
     * // Delete with specific date
     * DeleteAction::run($tagTeam, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $deletionDate = null): void
    {
        $tagTeam->ensureCanBeDeleted();

        $deletionDate = DateHelper::resolveDate($deletionDate);

        DB::transaction(function () use ($tagTeam, $deletionDate): void {
            // Handle tag team status using StatusTransitionPipeline
            if ($tagTeam->isEmployed()) {
                // Use pipeline to properly handle release (ends employment and suspension)
                StatusTransitionPipeline::release($tagTeam, $deletionDate)->execute();
            }
            // Note: Retired tag teams remain retired - no status change needed
            // Retirement is their natural end state, no artificial reactivation required

            // End current wrestler partnerships (wrestlers continue as singles)
            $tagTeam->currentWrestlers->each(function ($wrestler) use ($tagTeam, $deletionDate) {
                $tagTeam->wrestlers()->updateExistingPivot($wrestler->id, [
                    'left_at' => $deletionDate,
                ]);
            });

            // End current manager relationships
            $tagTeam->currentManagers->each(function ($manager) use ($tagTeam, $deletionDate) {
                $tagTeam->managers()->updateExistingPivot($manager->id, [
                    'fired_at' => $deletionDate,
                ]);
            });

            // Soft delete the tag team record
            $tagTeam->delete();
        });
    }
}
