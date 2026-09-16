<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Lifecycle\Roster\TagTeams\TagTeamSuspensionEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReinstateAction
{
    public function __construct(
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly TagTeamSuspensionEligibility $eligibility,
        private readonly ReinstateCurrentMembersAction $reinstateCurrentMembers,
    ) {}

    /**
     * Reinstate a suspended tag team and its current members.
     */
    public function handle(TagTeam $tagTeam, ?Carbon $reinstatementDate = null): void
    {
        $effectiveDate = $reinstatementDate ?? now();

        DB::transaction(function () use ($tagTeam, $effectiveDate): void {
            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->eligibility->ensureCanReinstate($lockedTagTeam);
            $this->suspensionPeriods->end($lockedTagTeam, $effectiveDate, LifecycleTransitionType::Reinstated);
            $this->reinstateCurrentMembers->handle($lockedTagTeam, $effectiveDate);
        });
    }
}
