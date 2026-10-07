<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Lifecycle\Periods\CareerPeriodCloser;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly CareerPeriodCloser $careerPeriods,
        private readonly IndividualEmploymentEligibility $eligibility,
    ) {}

    /**
     * Release a referee from employment.
     */
    public function handle(Referee $referee, ?Carbon $releaseDate = null): void
    {
        $effectiveDate = $releaseDate ?? now();

        DB::transaction(function () use ($referee, $effectiveDate): void {
            $lockedReferee = $referee->refreshForUpdate();

            $this->eligibility->ensureCanRelease($lockedReferee);
            $this->careerPeriods->release($lockedReferee, $effectiveDate);
        });
    }
}
