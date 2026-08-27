<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\TagTeamEmploymentEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TagTeamEmploymentService
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly TagTeamEmploymentEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(TagTeam, Carbon): void|null  $afterEmployment
     */
    public function employ(
        TagTeam $tagTeam,
        Carbon $employmentDate,
        ?Closure $afterEmployment = null,
    ): void {
        DB::transaction(function () use ($tagTeam, $employmentDate, $afterEmployment): void {
            $lockedTagTeam = TagTeam::query()
                ->whereKey($tagTeam->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanEmploy($lockedTagTeam);
            $this->employmentPeriods->start($lockedTagTeam, $employmentDate, LifecycleTransitionType::Employed);
            $afterEmployment?->__invoke($lockedTagTeam, $employmentDate);
        });
    }
}
