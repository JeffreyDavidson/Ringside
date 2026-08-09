<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Roster\CannotBeReleasedException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\InjuryPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly InjuryPeriodManager $injuryPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
    ) {}

    /**
     * Release a referee from employment.
     *
     * This handles the complete referee release workflow:
     * - Validates the referee can be released (currently employed)
     * - Ends suspension and injury if active
     * - Ends employment period with the specified date
     * - Maintains all historical records for tracking purposes
     *
     * @param  Referee  $referee  The referee to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When referee cannot be released due to business rules
     */
    public function handle(Referee $referee, ?Carbon $releaseDate = null): void
    {
        $referee->ensureCanBeReleased();

        $releaseDate = DateHelper::resolveDate($releaseDate);

        DB::transaction(function () use ($referee, $releaseDate): void {
            if ($referee->isSuspended()) {
                $this->suspensionPeriods->end($referee, $releaseDate);
            } elseif ($referee->isInjured()) {
                $this->injuryPeriods->end($referee, $releaseDate);
            }

            $this->employmentPeriods->end($referee, $releaseDate);
        });
    }
}
