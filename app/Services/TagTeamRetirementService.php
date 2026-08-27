<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\EmploymentPeriodManager;
use App\Lifecycle\RetirementPeriodManager;
use App\Lifecycle\SuspensionPeriodManager;
use App\Lifecycle\TagTeamRetirementEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TagTeamRetirementService
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly TagTeamRetirementEligibility $eligibility,
    ) {}

    /**
     * @param  Closure(TagTeam, Carbon): void|null  $afterRetirement
     */
    public function retire(
        TagTeam $tagTeam,
        Carbon $retirementDate,
        bool $retireMembers = true,
        ?Closure $afterRetirement = null,
    ): void {
        DB::transaction(function () use ($tagTeam, $retirementDate, $retireMembers, $afterRetirement): void {
            $lockedTagTeam = TagTeam::query()
                ->whereKey($tagTeam->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanRetire($lockedTagTeam);

            if ($lockedTagTeam->isEmployed()) {
                $this->employmentPeriods->end($lockedTagTeam, $retirementDate);
            }

            if ($lockedTagTeam->isSuspended()) {
                $this->suspensionPeriods->end($lockedTagTeam, $retirementDate);
            }

            $this->retirementPeriods->start($lockedTagTeam, $retirementDate, LifecycleTransitionType::Retired);

            if ($retireMembers) {
                $afterRetirement?->__invoke($lockedTagTeam, $retirementDate);
            }
        });
    }
}
