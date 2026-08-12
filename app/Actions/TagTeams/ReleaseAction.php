<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Individuals\CannotBeReleasedException;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReleaseAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
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
        $tagTeam->ensureCanBeReleased();

        $releaseDate = DateHelper::resolveDate($releaseDate);

        DB::transaction(function () use ($tagTeam, $releaseDate): void {
            $this->employmentPeriods->end($tagTeam, $releaseDate, LifecycleTransitionType::Released);

            if ($tagTeam->isSuspended()) {
                $this->suspensionPeriods->end($tagTeam, $releaseDate);
            }

            $this->endCurrentRelationships->handle($tagTeam, $releaseDate);
        });
    }
}
