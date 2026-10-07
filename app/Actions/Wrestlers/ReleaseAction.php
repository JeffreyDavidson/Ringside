<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Lifecycle\Periods\CareerPeriodCloser;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly CareerPeriodCloser $careerPeriods,
        private readonly IndividualEmploymentEligibility $eligibility,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
    ) {}

    /**
     * Release a wrestler from employment and end all current relationships.
     */
    public function handle(Wrestler $wrestler, ?Carbon $releaseDate = null): void
    {
        $effectiveDate = $releaseDate ?? now();

        DB::transaction(function () use ($wrestler, $effectiveDate): void {
            $lockedWrestler = $wrestler->refreshForUpdate();

            $this->eligibility->ensureCanRelease($lockedWrestler);
            $this->careerPeriods->release($lockedWrestler, $effectiveDate);

            $this->endCurrentRelationships->handle($lockedWrestler, $effectiveDate);
        });
    }
}
