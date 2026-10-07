<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Lifecycle\Periods\CareerPeriodCloser;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Models\Roster\Managers\Manager;
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
     * Release a manager from employment and end all current relationships.
     */
    public function handle(Manager $manager, ?Carbon $releaseDate = null): void
    {
        $effectiveDate = $releaseDate ?? now();

        DB::transaction(function () use ($manager, $effectiveDate): void {
            $lockedManager = $manager->refreshForUpdate();

            $this->eligibility->ensureCanRelease($lockedManager);
            $this->careerPeriods->release($lockedManager, $effectiveDate);

            $this->endCurrentRelationships->handle($lockedManager, $effectiveDate);
        });
    }
}
