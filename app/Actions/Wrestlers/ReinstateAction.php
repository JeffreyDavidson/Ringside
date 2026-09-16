<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualSuspensionEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReinstateAction
{
    public function __construct(
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly IndividualSuspensionEligibility $eligibility,
    ) {}

    /**
     * Reinstate a wrestler and make them available for employment.
     *
     * This handles the complete wrestler reinstatement workflow:
     * - Validates the wrestler can be reinstated
     * - Ends the suspension period through the shared lifecycle component
     * - Makes the wrestler available for new employment opportunities
     *
     * @param  Wrestler  $wrestler  The wrestler to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     * @throws CannotBeReinstatedException When wrestler cannot be reinstated due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $reinstatementDate = null): void
    {
        $effectiveDate = $reinstatementDate ?? now();

        DB::transaction(function () use ($wrestler, $effectiveDate): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            $this->eligibility->ensureCanReinstate($lockedWrestler);
            $this->suspensionPeriods->end($lockedWrestler, $effectiveDate, LifecycleTransitionType::Reinstated);
        });
    }
}
