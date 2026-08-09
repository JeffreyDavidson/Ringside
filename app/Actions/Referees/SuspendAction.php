<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Roster\CannotBeSuspendedException;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;

class SuspendAction
{
    public function __construct(private readonly SuspensionPeriodManager $suspensionPeriods) {}

    /**
     * Suspend a referee.
     *
     * This handles the complete referee suspension workflow:
     * - Validates the referee can be suspended (currently employed, not already suspended)
     * - Creates a suspension record with the specified start date
     * - Removes the referee from active match officiating duties
     * - Maintains employment status while restricting availability
     *
     * @param  Referee  $referee  The referee to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     * @throws CannotBeSuspendedException When referee cannot be suspended due to business rules
     */
    public function handle(Referee $referee, ?Carbon $suspensionDate = null): void
    {
        $referee->ensureCanBeSuspended();

        $suspensionDate = DateHelper::resolveDate($suspensionDate);

        $this->suspensionPeriods->start($referee, $suspensionDate);
    }
}
