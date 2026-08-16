<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\IndividualEmploymentEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly EmployCurrentManagersAction $employCurrentManagers,
        private readonly IndividualEmploymentEligibility $eligibility,
    ) {}

    /**
     * Employ a wrestler and activate their career.
     *
     * This handles the complete wrestler employment workflow:
     * - Validates the wrestler can be employed (not retired, not already employed)
     * - Prepares the wrestler by ending any active suspension or injury status
     * - Creates the employment record through the shared lifecycle component
     * - Employs any current managers who are not yet employed through cascading
     * - Makes the wrestler available for match bookings and storylines
     *
     * @param  Wrestler  $wrestler  The wrestler to employ
     * @param  Carbon|null  $employmentDate  The employment start date (defaults to now)
     * @throws CannotBeEmployedException When the wrestler cannot be employed
     */
    public function handle(Wrestler $wrestler, ?Carbon $employmentDate = null): void
    {
        $employmentDate = DateHelper::resolveDate($employmentDate);

        DB::transaction(function () use ($wrestler, $employmentDate): void {
            $lockedWrestler = Wrestler::query()->lockForUpdate()->findOrFail($wrestler->getKey());
            $this->eligibility->ensureCanEmploy($lockedWrestler);

            $this->employmentPeriods->start($lockedWrestler, $employmentDate, LifecycleTransitionType::Employed);
            $this->employCurrentManagers->handle($lockedWrestler, $employmentDate);
        });
    }
}
