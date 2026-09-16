<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Roster\Stables\StableDeletionEligibility;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly StableDeletionEligibility $eligibility,
    ) {}

    /**
     * Delete a stable.
     *
     * The stable must already be inactive and have no current members. Those
     * transitions remain explicit operations so deletion only changes record state.
     *
     * @param  Stable  $stable  The stable to delete
     */
    public function handle(Stable $stable, ?Carbon $deletionDate = null): void
    {
        $effectiveDate = $deletionDate ?? now();

        DB::transaction(function () use ($stable, $effectiveDate): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->eligibility->ensureCanDelete($lockedStable);
            $this->deletionState->delete($lockedStable, $effectiveDate);
        });
    }
}
