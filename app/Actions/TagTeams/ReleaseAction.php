<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Lifecycle\Periods\CareerPeriodCloser;
use App\Lifecycle\Roster\TagTeams\TagTeamEmploymentEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly CareerPeriodCloser $careerPeriods,
        private readonly TagTeamEmploymentEligibility $eligibility,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
    ) {}

    /**
     * Release a tag team from employment and end all current relationships.
     */
    public function handle(TagTeam $tagTeam, ?Carbon $releaseDate = null): void
    {
        $effectiveDate = $releaseDate ?? now();

        DB::transaction(function () use ($tagTeam, $effectiveDate): void {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->eligibility->ensureCanRelease($lockedTagTeam);
            $this->careerPeriods->release($lockedTagTeam, $effectiveDate);

            $this->endCurrentRelationships->handle($lockedTagTeam, $effectiveDate);
        });
    }
}
