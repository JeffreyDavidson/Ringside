<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmployAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly IndividualEmploymentEligibility $eligibility,
        private readonly EmployCurrentManagersAction $employCurrentManagers,
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
        $effectiveDate = $employmentDate ?? now();

        DB::transaction(function () use ($wrestler, $effectiveDate): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            $this->eligibility->ensureCanEmploy($lockedWrestler);
            $this->employmentPeriods->start($lockedWrestler, $effectiveDate, LifecycleTransitionType::Employed);
            $this->employCurrentManagers->handle($lockedWrestler, $effectiveDate);
        });
    }
}
