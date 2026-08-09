<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Roster\CannotBeUnretiredException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\RetirementPeriodManager;
use App\Models\Referees\Referee;
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
     * Unretire a retired referee and return them to active officiating.
     *
     * This handles the complete referee unretirement workflow:
     * - Validates the referee can be unretired (currently retired)
     * - Ends the current retirement period through RetirementPeriodManager
     * - Starts a new employment period from the unretirement date
     * - Restores the referee to available status for match assignments
     * - Preserves all historical retirement and employment records
     *
     * @param  Referee  $referee  The referee to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @throws CannotBeUnretiredException When referee cannot be unretired due to business rules
     */
    public function handle(Referee $referee, ?Carbon $unretiredDate = null): void
    {
        $referee->ensureCanBeUnretired();

        $unretiredDate = DateHelper::resolveDate($unretiredDate);

        DB::transaction(function () use ($referee, $unretiredDate): void {
            $this->retirementPeriods->end($referee, $unretiredDate);
            $this->employmentPeriods->start($referee, $unretiredDate);
        });
    }
}
