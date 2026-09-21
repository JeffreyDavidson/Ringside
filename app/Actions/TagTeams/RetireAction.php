<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Lifecycle\Roster\TagTeams\TagTeamRetirementEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(
        private readonly EmploymentPeriodManager $employmentPeriods,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly TagTeamRetirementEligibility $eligibility,
        private readonly RetireCurrentMembersAction $retireCurrentMembers,
    ) {}

    /**
     * Retire a tag team and optionally its current members.
     */
    public function handle(TagTeam $tagTeam, ?Carbon $retirementDate = null, bool $retireMembers = true): void
    {
        $effectiveDate = $retirementDate ?? now();

        DB::transaction(function () use ($tagTeam, $effectiveDate, $retireMembers): void {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->eligibility->ensureCanRetire($lockedTagTeam);

            if ($lockedTagTeam->currentEmployment()->exists()) {
                $this->employmentPeriods->end($lockedTagTeam, $effectiveDate);
            }

            if ($lockedTagTeam->currentSuspension()->exists()) {
                $this->suspensionPeriods->end($lockedTagTeam, $effectiveDate);
            }

            $this->retirementPeriods->start($lockedTagTeam, $effectiveDate, LifecycleTransitionType::Retired);

            if ($retireMembers) {
                $this->retireCurrentMembers->handle($lockedTagTeam, $effectiveDate);
            }
        });
    }
}
