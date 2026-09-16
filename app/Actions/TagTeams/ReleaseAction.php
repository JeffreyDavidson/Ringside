<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Lifecycle\Roster\TagTeams\TagTeamEmploymentEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
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
            $this->employmentPeriods->end($lockedTagTeam, $effectiveDate, LifecycleTransitionType::Released);

            if ($lockedTagTeam->currentSuspension()->exists()) {
                $this->suspensionPeriods->end($lockedTagTeam, $effectiveDate);
            }

            $this->endCurrentRelationships->handle($lockedTagTeam, $effectiveDate);
        });
    }
}
