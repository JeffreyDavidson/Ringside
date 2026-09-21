<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Lifecycle\Periods\DeletionPeriodCloser;
use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Roster\Individuals\IndividualDeletionEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(
        private readonly DeletionPeriodCloser $periods,
        private readonly DeletionStateManager $deletionState,
        private readonly IndividualDeletionEligibility $eligibility,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
    ) {}

    /**
     * Delete a wrestler.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * EMPLOYMENT IMPACT:
     * - Ends active employment, retirement, suspension, and injury periods
     * - Preserves wrestler employment history for administrative records
     *
     * RELATIONSHIP IMPACT:
     * - Ends all current professional relationships through a typed domain action
     * - Removes wrestler from current tag teams (teams may need new members)
     * - Ends stable memberships (stables continue with remaining members)
     * - Terminates management contracts (managers may manage other talent)
     * - Vacates any held championships (titles become available)
     *
     * OTHER CLEANUP:
     * - Soft deletes the wrestler record
     * - Maintains referential integrity with historical data
     */
    public function handle(Wrestler $wrestler, ?Carbon $deletionDate = null): void
    {
        $effectiveDate = $deletionDate ?? now();

        DB::transaction(function () use ($wrestler, $effectiveDate): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            $this->eligibility->ensureCanDelete($lockedWrestler);
            $this->periods->close($lockedWrestler, $effectiveDate);
            $this->endCurrentRelationships->handle($lockedWrestler, $effectiveDate);
            $this->deletionState->delete($lockedWrestler, $effectiveDate);
        });
    }
}
