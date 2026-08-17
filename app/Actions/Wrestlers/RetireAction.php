<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\IndividualRetirementEligibility;
use App\Lifecycle\InjuryPeriodManager;
use App\Lifecycle\RetirementPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
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
        $retirementDate = DateHelper::resolveDate($retirementDate);

        DB::transaction(function () use ($wrestler, $retirementDate): void {
            $lockedWrestler = Wrestler::query()->whereKey($wrestler->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanRetire($lockedWrestler);

            if ($lockedWrestler->isEmployed()) {
                $this->employmentPeriods->end($lockedWrestler, $retirementDate);
            }

            if ($lockedWrestler->isSuspended()) {
                $this->suspensionPeriods->end($lockedWrestler, $retirementDate);
            } elseif ($lockedWrestler->isInjured()) {
                $this->injuryPeriods->end($lockedWrestler, $retirementDate);
            }

            $this->retirementPeriods->start($lockedWrestler, $retirementDate, LifecycleTransitionType::Retired);
            $this->endCurrentRelationships->handle($lockedWrestler, $retirementDate);
        });
    }
}
