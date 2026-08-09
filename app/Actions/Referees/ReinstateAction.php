<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Roster\CannotBeReinstatedException;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;

class ReinstateAction
{
    public function __construct(private readonly SuspensionPeriodManager $suspensionPeriods) {}

    /**
     * Reinstate a suspended referee.
     *
     * This handles the complete referee reinstatement workflow:
     * - Validates the referee can be reinstated (currently suspended)
     * - Ends the current suspension period with the specified date
     * - Restores the referee to active officiating status
     * - Makes the referee available for match assignments
     *
     * @param  Referee  $referee  The referee to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     * @throws CannotBeReinstatedException When referee cannot be reinstated due to business rules
     */
    public function handle(Referee $referee, ?Carbon $reinstatementDate = null): void
    {
        $referee->ensureCanBeReinstated();

        $reinstatementDate = DateHelper::resolveDate($reinstatementDate);

        $this->suspensionPeriods->end($referee, $reinstatementDate);
    }
}
