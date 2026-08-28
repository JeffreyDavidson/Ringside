<?php

declare(strict_types=1);

namespace App\Services\Roster\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\Roster\TagTeams\TagTeamEmploymentEligibility;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Roster\TagTeams\TagTeam;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TagTeamReleaseService
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly TagTeamEmploymentEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(TagTeam, Carbon): void|null  $afterRelease
     */
    public function release(
        TagTeam $tagTeam,
        Carbon $releaseDate,
        ?Closure $afterRelease = null,
    ): void {
        DB::transaction(function () use ($tagTeam, $releaseDate, $afterRelease): void {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->eligibility->ensureCanRelease($lockedTagTeam);
            $this->employmentPeriods->end($lockedTagTeam, $releaseDate, LifecycleTransitionType::Released);

            if ($lockedTagTeam->isSuspended()) {
                $this->suspensionPeriods->end($lockedTagTeam, $releaseDate);
            }

            $afterRelease?->__invoke($lockedTagTeam, $releaseDate);
        });
    }
}
