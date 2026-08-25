<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\Roster\Individuals\CannotBeReleasedException;
use App\Lifecycle\IndividualEmploymentEligibility;
use App\Models\Roster\Referees\Referee;
use App\Services\IndividualReleasePeriodService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly IndividualReleasePeriodService $releasePeriods,
        private readonly IndividualEmploymentEligibility $eligibility,
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
        $releaseDate = $releaseDate ?? now();

        DB::transaction(function () use ($referee, $releaseDate): void {
            $lockedReferee = Referee::query()->whereKey($referee->getKey())->lockForUpdate()->firstOrFail();
            $this->eligibility->ensureCanRelease($lockedReferee);

            $this->releasePeriods->end($lockedReferee, $releaseDate);
        });
    }
}
