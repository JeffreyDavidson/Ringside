<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\IndividualDeletionEligibility;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
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
        $this->eligibility->ensureCanRestore($wrestler);

        $restoreDate = DateHelper::resolveDate($restoreDate);

        DB::transaction(function () use ($wrestler, $restoreDate): void {
            $this->deletionState->restore($wrestler, $restoreDate);

            // Note: No automatic relationship restoration to avoid conflicts.
            // All employment, tag team, stable, and manager relationships
            // must be re-established explicitly using separate actions.
        });
    }
}
