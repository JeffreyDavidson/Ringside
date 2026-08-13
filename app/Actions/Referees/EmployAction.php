<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\IndividualEmploymentEligibility;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly IndividualEmploymentEligibility $eligibility,
    ) {}

    /**
     * Employ a referee.
     *
     * This handles the complete referee employment workflow:
     * - Validates the referee can be employed (not retired, not already employed)
     * - Creates the employment record through the shared lifecycle component
     * - Makes the referee available for match officiating assignments
     *
     * @param  Referee  $referee  The referee to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     * @throws CannotBeEmployedException When the referee cannot be employed
     */
    public function handle(Referee $referee, ?Carbon $employmentDate = null): void
    {
        $employmentDate = DateHelper::resolveDate($employmentDate);

        DB::transaction(function () use ($referee, $employmentDate): void {
            $lockedReferee = Referee::query()->lockForUpdate()->findOrFail($referee->getKey());
            $this->eligibility->ensureCanEmploy($lockedReferee);

            $this->employmentPeriods->start($lockedReferee, $employmentDate, LifecycleTransitionType::Employed);
        });
    }
}
