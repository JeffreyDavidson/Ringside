<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Actions\TagTeams\RetireAction as TagTeamsRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Roster\Individuals\IndividualRetirementEligibility;
use App\Lifecycle\Roster\Stables\StableRetirementEligibility;
use App\Lifecycle\Roster\TagTeams\TagTeamRetirementEligibility;
use App\Models\Roster\Stables\Stable;
use App\Services\Roster\Stables\StableMembershipService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    /**
     * Create a new retire action instance.
     */
    public function __construct(
        protected WrestlersRetireAction $wrestlersRetireAction,
        protected TagTeamsRetireAction $tagTeamsRetireAction,
        protected EndActivityPeriodAction $endActivityPeriodAction,
        protected RetirementPeriodManager $retirementPeriods,
        protected StableRetirementEligibility $stableRetirementEligibility,
        protected RemoveStableMembersAction $removeStableMembersAction,
        protected IndividualRetirementEligibility $individualRetirementEligibility,
        protected TagTeamRetirementEligibility $tagTeamRetirementEligibility,
        protected StableMembershipService $membershipService,
    ) {}

    /**
     * Retire a stable and end its operations.
     */
    public function handle(Stable $stable, ?Carbon $retirementDate = null): void
    {
        $retirementDate ??= now();
        $operationalDate = $retirementDate->isFuture() ? now() : $retirementDate;

        DB::transaction(function () use ($stable, $retirementDate, $operationalDate): void {
            $lockedStable = $stable->refreshForUpdate();

            $this->stableRetirementEligibility->ensureCanRetire($lockedStable);

            if ($lockedStable->currentActivityPeriod()->exists()) {
                $this->endActivityPeriodAction->handle($lockedStable, $operationalDate);
            }

            $currentMembers = $this->membershipService->currentMembers($lockedStable);
            $this->removeStableMembersAction->handle($lockedStable, $currentMembers, $operationalDate);

            if ($currentMembers->wrestlers instanceof Collection) {
                foreach ($currentMembers->wrestlers as $wrestler) {
                    if ($this->individualRetirementEligibility->canRetire($wrestler)) {
                        $this->wrestlersRetireAction->handle($wrestler, $retirementDate);
                    }
                }
            }

            if ($currentMembers->tagTeams instanceof Collection) {
                foreach ($currentMembers->tagTeams as $tagTeam) {
                    if ($this->tagTeamRetirementEligibility->canRetire($tagTeam)) {
                        $this->tagTeamsRetireAction->handle($tagTeam, $retirementDate);
                    }
                }
            }

            $this->retirementPeriods->start($lockedStable, $retirementDate, LifecycleTransitionType::Retired);
        });
    }
}
