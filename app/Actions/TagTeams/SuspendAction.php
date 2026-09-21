<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Lifecycle\Roster\TagTeams\TagTeamSuspensionEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SuspendAction
{
    public function __construct(
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly TagTeamSuspensionEligibility $eligibility,
        private readonly SuspendCurrentMembersAction $suspendCurrentMembers,
    ) {}

    /**
     * Suspend a tag team and its current members.
     */
    public function handle(TagTeam $tagTeam, ?Carbon $suspensionDate = null): void
    {
        $effectiveDate = $suspensionDate ?? now();

        DB::transaction(function () use ($tagTeam, $effectiveDate): void {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->eligibility->ensureCanSuspend($lockedTagTeam);
            $this->suspensionPeriods->start($lockedTagTeam, $effectiveDate, LifecycleTransitionType::Suspended);
            $this->suspendCurrentMembers->handle($lockedTagTeam, $effectiveDate);
        });
    }
}
