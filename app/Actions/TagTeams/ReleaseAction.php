<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\TagTeams\CannotBeReleasedException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Lifecycle\TagTeamEmploymentEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
        private readonly TagTeamEmploymentEligibility $eligibility,
    ) {}

    /**
     * Release a tag team from employment and end all current relationships.
     *
     * This handles the complete tag team release workflow:
     * - Validates the tag team can be released (currently employed)
     * - Ends employment and suspension through lifecycle period managers
     * - Ends current wrestler partnerships (wrestlers become free agents)
     * - Ends current manager relationships (managers remain available)
     * - Maintains all historical records for tracking purposes
     * - Individual members retain employment status and may form new partnerships
     *
     * @param  TagTeam  $tagTeam  The tag team to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When tag team cannot be released due to business rules
     */
    public function handle(TagTeam $tagTeam, ?Carbon $releaseDate = null): void
    {
        $releaseDate = DateHelper::resolveDate($releaseDate);

        DB::transaction(function () use ($tagTeam, $releaseDate): void {
            $lockedTagTeam = TagTeam::query()->lockForUpdate()->findOrFail($tagTeam->getKey());
            $this->eligibility->ensureCanRelease($lockedTagTeam);

            $this->employmentPeriods->end($lockedTagTeam, $releaseDate, LifecycleTransitionType::Released);

            if ($lockedTagTeam->isSuspended()) {
                $this->suspensionPeriods->end($lockedTagTeam, $releaseDate);
            }

            $this->endCurrentRelationships->handle($lockedTagTeam, $releaseDate);
        });
    }
}
