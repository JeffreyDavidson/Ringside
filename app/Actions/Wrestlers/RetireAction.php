<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Lifecycle\Periods\CareerPeriodCloser;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualRetirementEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(
        private readonly CareerPeriodCloser $careerPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly IndividualRetirementEligibility $eligibility,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
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
     * @param  TagTeam|null  $retainedTagTeam  A retiring tag team the wrestler stays a member of
     *
     * @throws CannotBeRetiredException When wrestler cannot be retired due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $retirementDate = null, ?TagTeam $retainedTagTeam = null): void
    {
        $effectiveDate = $retirementDate ?? now();

        DB::transaction(function () use ($wrestler, $effectiveDate, $retainedTagTeam): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            $this->eligibility->ensureCanRetire($lockedWrestler);

            $this->careerPeriods->retire($lockedWrestler, $effectiveDate);

            $this->retirementPeriods->start($lockedWrestler, $effectiveDate, LifecycleTransitionType::Retired);
            $this->endCurrentRelationships->handle($lockedWrestler, $effectiveDate, $retainedTagTeam);
        });
    }
}
