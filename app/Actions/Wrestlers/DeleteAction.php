<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Delete a wrestler.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * TAG TEAM IMPACT:
     * - Removes wrestler from current tag teams
     * - 0 wrestlers left: Marks tag team as dissolved/inactive
     * - 1 wrestler left: Marks tag team as not bookable (seeking partner)
     * - 2+ wrestlers left: Tag team continues normally
     *
     * STABLE IMPACT:
     * - Removes wrestler from current stable
     * - Checks minimum membership (3 members minimum)
     * - Below minimum: Marks stable as inactive or dissolved
     * - At/above minimum: Stable continues normally
     *
     * MANAGER IMPACT:
     * - Ends management relationships with current managers
     * - Managers continue their careers (may manage other wrestlers)
     * - No immediate impact on manager employment status
     *
     * OTHER CLEANUP:
     * - Ends employment, suspension, and injury if active
     * - Soft deletes the wrestler record
     */
    public function handle(Wrestler $wrestler, ?Carbon $deletionDate = null): void
    {
        $deletionDate = $this->getEffectiveDate($deletionDate);

        DB::transaction(function () use ($wrestler, $deletionDate): void {
            // Handle wrestler status - employed wrestlers can be suspended/injured, retired wrestlers are not employed
            if ($wrestler->isEmployed()) {
                // End suspension or injury if active (employed wrestler cannot be both)
                if ($wrestler->isSuspended()) {
                    $this->wrestlerRepository->endSuspension($wrestler, $deletionDate);
                } elseif ($wrestler->isInjured()) {
                    $this->wrestlerRepository->endInjury($wrestler, $deletionDate);
                }

                // End employment
                $this->wrestlerRepository->endEmployment($wrestler, $deletionDate);
            } elseif ($wrestler->isRetired()) {
                // End retirement if active (retired wrestlers are not employed)
                $this->wrestlerRepository->endRetirement($wrestler, $deletionDate);
            }

            // Handle tag team impact if wrestler is in a current tag team
            if ($wrestler->currentTagTeam !== null) {
                // Remove the wrestler from the tag team
                $this->wrestlerRepository->removeFromCurrentTagTeam($wrestler, $deletionDate);

                // Note: Tag team bookability is handled automatically by the isBookable() method
                // which checks if the team has sufficient active members
            }

            // Handle manager relationships - end management relationships
            $this->removeCurrentManagers($wrestler, $deletionDate);

            // Soft delete the wrestler record
            $this->wrestlerRepository->delete($wrestler);
        });
    }
}
