<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeReleasedException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\IndividualEmploymentEligibility;
use App\Lifecycle\InjuryPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
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
     *
     * This handles the complete referee release workflow:
     * - Validates the referee can be released (currently employed)
     * - Ends suspension and injury if active
     * - Ends employment period with the specified date
     * - Maintains all historical records for tracking purposes
     *
     * @param  Referee  $referee  The referee to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When referee cannot be released due to business rules
     */
    public function handle(Referee $referee, ?Carbon $releaseDate = null): void
    {
        $releaseDate = DateHelper::resolveDate($releaseDate);

        DB::transaction(function () use ($referee, $releaseDate): void {
            $lockedReferee = Referee::query()->lockForUpdate()->findOrFail($referee->getKey());
            $this->eligibility->ensureCanRelease($lockedReferee);

            if ($lockedReferee->isSuspended()) {
                $this->suspensionPeriods->end($lockedReferee, $releaseDate);
            } elseif ($lockedReferee->isInjured()) {
                $this->injuryPeriods->end($lockedReferee, $releaseDate);
            }

            $this->employmentPeriods->end($lockedReferee, $releaseDate, LifecycleTransitionType::Released);
        });
    }
}
