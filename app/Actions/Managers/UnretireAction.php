<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\Roster\CannotBeUnretiredException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\RetirementPeriodManager;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnretireAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
    ) {}

    /**
     * Unretire a retired manager and return them to active talent management.
     *
     * This handles the complete manager unretirement workflow:
     * - Uses StatusTransitionPipeline for consistent unretirement handling
     * - Validates the manager can be unretired (currently retired)
     * - Ends the current retirement period with the specified date
     * - Creates a new employment record starting from the unretirement date
     * - Restores the manager to available status for wrestler and tag team assignments
     * - Preserves all historical retirement and employment records
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistent status handling, following the same
     * pattern as other manager actions.
     *
     * @param  Manager  $manager  The manager to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @throws CannotBeUnretiredException When manager cannot be unretired due to business rules
     */
    public function handle(Manager $manager, ?Carbon $unretiredDate = null, bool $employImmediately = true): void
    {
        $manager->ensureCanBeUnretired();

        $unretiredDate = DateHelper::resolveDate($unretiredDate);

        DB::transaction(function () use ($manager, $unretiredDate, $employImmediately): void {
            $this->retirementPeriods->end($manager, $unretiredDate);

            if ($employImmediately) {
                $this->employmentPeriods->start($manager, $unretiredDate);
            }
        });
    }
}
