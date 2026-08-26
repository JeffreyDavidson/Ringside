<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Lifecycle\IndividualRetirementEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\IndividualRetirementPeriodService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(
        private readonly IndividualRetirementPeriodService $retirementPeriods,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
        private readonly IndividualRetirementEligibility $eligibility,
    ) {}

    /**
     * Retire a wrestler and end their career.
     *
     * This handles the complete wrestler retirement workflow:
     * - Validates the wrestler can be retired
     * - Ends employment, suspension, and injury through lifecycle period managers
     * - Ends all current professional relationships through a typed domain action
     * - Starts a retirement period
     * - Makes the wrestler permanently unavailable for competition
     * - Preserves the operation's transaction boundary
     *
     * @param  Wrestler  $wrestler  The wrestler to retire
     * @param  Carbon|null  $retirementDate  The retirement start date (defaults to now)
     * @throws CannotBeRetiredException When wrestler cannot be retired due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $retirementDate = null): void
    {
        $retirementDate = $retirementDate ?? now();

        DB::transaction(function () use ($wrestler, $retirementDate): void {
            $lockedWrestler = Wrestler::query()->whereKey($wrestler->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanRetire($lockedWrestler);

            $this->retirementPeriods->start($lockedWrestler, $retirementDate);
            $this->endCurrentRelationships->handle($lockedWrestler, $retirementDate);
        });
    }
}
