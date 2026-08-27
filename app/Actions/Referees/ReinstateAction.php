<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Models\Roster\Referees\Referee;
use App\Services\Roster\Individuals\IndividualSuspensionService;
use Illuminate\Support\Carbon;

class ReinstateAction
{
    public function __construct(
        private readonly IndividualSuspensionService $suspension,
    ) {}

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
        $this->suspension->reinstate($referee, $reinstatementDate ?? now());
    }
}
