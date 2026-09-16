<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualRetirementEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnretireAction
{
    public function __construct(
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly IndividualRetirementEligibility $eligibility,
        private readonly EmployAction $employ,
    ) {}

    /**
     * Unretire a wrestler and return them to active competition.
     *
     * This handles the complete wrestler comeback workflow with flexible employment options:
     * - Validates the wrestler can come out of retirement (business rule compliance)
     * - Ends the current retirement period through RetirementPeriodManager
     * - Optionally employs the wrestler immediately or leaves unemployed for manual employment
     * - Restores the wrestler to available status for match bookings
     * - Makes the wrestler available for new career opportunities
     * - Preserves all historical retirement records
     *
     * @param  Wrestler  $wrestler  The wrestler to unretire
     * @param  Carbon|null  $unretirementDate  The unretirement date (defaults to now)
     * @param  bool  $employImmediately  Whether to employ the wrestler immediately (default: true)
     *
     * @throws CannotBeUnretiredException When wrestler cannot be unretired due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $unretirementDate = null, bool $employImmediately = true): void
    {
        $effectiveDate = $unretirementDate ?? now();

        DB::transaction(function () use ($wrestler, $effectiveDate, $employImmediately): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            $this->eligibility->ensureCanUnretire($lockedWrestler);
            $this->retirementPeriods->end($lockedWrestler, $effectiveDate, LifecycleTransitionType::Unretired);

            if ($employImmediately) {
                $this->employ->handle($lockedWrestler, $effectiveDate);
            }
        });
    }
}
