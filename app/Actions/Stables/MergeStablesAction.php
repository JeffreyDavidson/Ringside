<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class MergeStablesAction extends BaseStableAction
{
    use AsAction;

    /**
     * Merge two stables into one.
     *
     * Transfers all members from the secondary stable to the primary stable
     * and optionally deletes the secondary stable if the operation is successful.
     *
     * @param  Stable  $primaryStable  The stable that will receive all members
     * @param  Stable  $secondaryStable  The stable that will be merged into the primary
     * @param  Carbon  $date  The date when the merge operation occurs
     */
    public function handle(
        Stable $primaryStable,
        Stable $secondaryStable,
        Carbon $date
    ): void {
        DB::transaction(function () use ($primaryStable, $secondaryStable, $date): void {
            // Transfer all wrestlers
            foreach ($secondaryStable->currentWrestlers as $wrestler) {
                $this->stableRepository->removeWrestler($secondaryStable, $wrestler, $date);
                $this->stableRepository->addWrestler($primaryStable, $wrestler, $date);
            }

            // Transfer all tag teams
            foreach ($secondaryStable->currentTagTeams as $tagTeam) {
                $this->stableRepository->removeTagTeam($secondaryStable, $tagTeam, $date);
                $this->stableRepository->addTagTeam($primaryStable, $tagTeam, $date);
            }

            // Note: Managers are not direct stable members and are automatically
            // transferred through their wrestlers/tag teams associations

            // Delete the secondary stable after successful transfers
            $secondaryStable->delete();
        });
    }
}
