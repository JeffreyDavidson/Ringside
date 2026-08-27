<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Models\Roster\Referees\Referee;
use App\Services\Roster\Individuals\IndividualRetirementService;
use Illuminate\Support\Carbon;

class RetireAction
{
    public function __construct(
        private readonly IndividualRetirementService $retirement,
    ) {}

    /**
     * Retire a referee and end their officiating career.
     *
     * This handles the complete referee retirement workflow:
     * - Validates the referee can be retired (currently employed/active)
     * - Ends suspension and injury if active
     * - Ends employment period if currently employed
     * - Creates retirement record to formally end their officiating career
     * - Makes the referee unavailable for future match assignments
     * - Preserves all historical records and match officiating history
     *
     * @param  Referee  $referee  The referee to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     * @throws CannotBeRetiredException When referee cannot be retired due to business rules
     */
    public function handle(Referee $referee, ?Carbon $retirementDate = null): void
    {
        $retirementDate = $retirementDate ?? now();

        $this->retirement->retire($referee, $retirementDate);
    }
}
