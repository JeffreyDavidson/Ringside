<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Periods\InjuryPeriodManager;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly IndividualEmploymentEligibility $eligibility,
    ) {}

    /**
     * Release a referee from employment.
     */
    public function handle(Referee $referee, ?Carbon $releaseDate = null): void
    {
        $effectiveDate = $releaseDate ?? now();

        DB::transaction(function () use ($referee, $effectiveDate): void {
            $lockedReferee = $referee->refreshForUpdate();

            $this->eligibility->ensureCanRelease($lockedReferee);

            $this->employmentPeriods->end($lockedReferee, $effectiveDate, LifecycleTransitionType::Released);

            if ($lockedReferee->currentSuspension()->exists()) {
                $this->suspensionPeriods->end($lockedReferee, $effectiveDate);
            } elseif ($lockedReferee->currentInjury()->exists()) {
                $this->injuryPeriods->end($lockedReferee, $effectiveDate);
            }
        });
    }
}
