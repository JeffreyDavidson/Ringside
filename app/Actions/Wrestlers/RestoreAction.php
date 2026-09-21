<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Roster\Individuals\IndividualDeletionEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RestoreAction
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly IndividualDeletionEligibility $eligibility,
    ) {}

    /**
     * Restore a soft-deleted wrestler record.
     *
     * This action only restores the wrestler record itself. All relationships
     * (employment, tag teams, stables, managers) must be re-established separately
     * using appropriate actions to avoid conflicts and provide explicit control.
     *
     * Use cases after restoration:
     * - EmployAction: To re-employ the wrestler
     * - TagTeam actions: To rejoin tag teams if appropriate
     * - Stable actions: To rejoin stables if appropriate
     * - Manager relationships: To re-establish management if appropriate
     */
    public function handle(Wrestler $wrestler, ?Carbon $restoreDate = null): void
    {
        $effectiveDate = $restoreDate ?? now();

        DB::transaction(function () use ($wrestler, $effectiveDate): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            $this->eligibility->ensureCanRestore($lockedWrestler);
            $this->deletionState->restore($lockedWrestler, $effectiveDate);
        });
    }
}
