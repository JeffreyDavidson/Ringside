<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction extends BaseTagTeamAction
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
     * - Ends tag team employment and suspension if active
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
        $deletionDate = $this->getEffectiveDate($deletionDate);

        DB::transaction(function () use ($tagTeam, $deletionDate): void {
            // Handle tag team status - employed tag teams can be suspended, retired tag teams are not employed
            if ($tagTeam->isEmployed()) {
                // End suspension if active
                if ($tagTeam->isSuspended()) {
                    $this->tagTeamRepository->endSuspension($tagTeam, $deletionDate);
                }

                // End employment
                $this->tagTeamRepository->endEmployment($tagTeam, $deletionDate);
            } elseif ($tagTeam->isRetired()) {
                // End retirement if active (retired tag teams are not employed)
                $this->tagTeamRepository->endRetirement($tagTeam, $deletionDate);
            }

            // End current wrestler partnerships (wrestlers continue as singles)
            $this->tagTeamRepository->removeWrestlers($tagTeam, $tagTeam->currentWrestlers, $deletionDate);

            // End current manager relationships
            $this->tagTeamRepository->removeManagers($tagTeam, $tagTeam->currentManagers, $deletionDate);

            // Soft delete the tag team record
            $this->tagTeamRepository->delete($tagTeam);
        });
    }
}
