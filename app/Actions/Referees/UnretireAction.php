<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Roster\Individuals\IndividualUnretirementService;
use Illuminate\Support\Carbon;

class UnretireAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly IndividualUnretirementService $unretirement,
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
        $unretiredDate = $unretiredDate ?? now();

        $this->unretirement->unretire($referee, $unretiredDate, function (Wrestler|Manager|Referee $lockedReferee, Carbon $date): void {
            if ($lockedReferee instanceof Referee) {
                $this->employmentPeriods->start($lockedReferee, $date, LifecycleTransitionType::Employed);
            }
        });
    }
}
