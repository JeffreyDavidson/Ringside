<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\WrestlerRetirementCascadeStrategy;
use App\Exceptions\Roster\CannotBeRetiredException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\InjuryPeriodManager;
use App\Lifecycle\RetirementPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Wrestlers\Wrestler;
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
    ) {}

    /**
     * Retire a wrestler and end their career.
     *
     * This handles the complete wrestler retirement workflow using StatusTransitionPipeline:
     * - Validates the wrestler can be retired through pipeline validation
     * - Uses StatusTransitionPipeline to properly handle retirement status transition
     * - Automatically ends employment, suspension, and injury through pipeline
     * - Cascades to end all professional relationships (partnerships, memberships, etc.)
     * - Creates retirement record and updates status through pipeline
     * - Makes the wrestler permanently unavailable for competition
     * - Maintains transaction boundaries and error handling through pipeline
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline with WrestlerRetirementCascadeStrategy for consistency
     * with other entity retirement operations and comprehensive relationship management.
     *
     * @param  Wrestler  $wrestler  The wrestler to retire
     * @param  Carbon|null  $retirementDate  The retirement start date (defaults to now)
     * @throws CannotBeRetiredException When wrestler cannot be retired due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $retirementDate = null): void
    {
        $wrestler->ensureCanBeRetired();

        $retirementDate = DateHelper::resolveDate($retirementDate);

        DB::transaction(function () use ($wrestler, $retirementDate): void {
            if ($wrestler->isEmployed()) {
                $this->employmentPeriods->end($wrestler, $retirementDate);
            }

            if ($wrestler->isSuspended()) {
                $this->suspensionPeriods->end($wrestler, $retirementDate);
            } elseif ($wrestler->isInjured()) {
                $this->injuryPeriods->end($wrestler, $retirementDate);
            }

            $this->retirementPeriods->start($wrestler, $retirementDate);
            WrestlerRetirementCascadeStrategy::endAllRelationships()($wrestler, $retirementDate, 'retire');
        });
    }
}
