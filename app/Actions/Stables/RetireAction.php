<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\TagTeams\RetireAction as TagTeamsRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Lifecycle\IndividualRetirementEligibility;
use App\Lifecycle\TagTeamRetirementEligibility;
use App\Models\Roster\Stables\Stable;
use App\Services\Roster\Stables\StableMembershipService;
use App\Services\Roster\Stables\StableRetirementService;
use Illuminate\Support\Carbon;

class RetireAction
{
    /**
     * Create a new retire action instance.
     */
    public function __construct(
        protected WrestlersRetireAction $wrestlersRetireAction,
        protected TagTeamsRetireAction $tagTeamsRetireAction,
        protected StableRetirementService $retirement,
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

        $this->retirement->retire($stable, $retirementDate, $operationalDate, function (Stable $lockedStable, Carbon $effectiveRetirementDate, Carbon $effectiveOperationalDate): void {
            $currentMembers = $this->membershipService->currentMembers($lockedStable);
            $this->removeStableMembersAction->handle($lockedStable, $currentMembers, $effectiveOperationalDate);

            if ($currentMembers->wrestlers) {
                foreach ($currentMembers->wrestlers as $wrestler) {
                    if ($this->individualRetirementEligibility->canRetire($wrestler)) {
                        $this->wrestlersRetireAction->handle($wrestler, $effectiveRetirementDate);
                    }
                }
            }

            if ($currentMembers->tagTeams) {
                foreach ($currentMembers->tagTeams as $tagTeam) {
                    if ($this->tagTeamRetirementEligibility->canRetire($tagTeam)) {
                        $this->tagTeamsRetireAction->handle($tagTeam, $effectiveRetirementDate);
                    }
                }
            }

        });
    }
}
