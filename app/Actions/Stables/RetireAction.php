<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Actions\TagTeams\RetireAction as TagTeamsRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Stables\CannotBeRetiredException;
use App\Lifecycle\IndividualRetirementEligibility;
use App\Lifecycle\RetirementPeriodManager;
use App\Lifecycle\StableRetirementEligibility;
use App\Lifecycle\TagTeamRetirementEligibility;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;
use Illuminate\Support\Carbon;
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
        protected RemoveStableMembersAction $removeStableMembersAction,
        protected RetirementPeriodManager $retirementPeriods,
        protected IndividualRetirementEligibility $individualRetirementEligibility,
        protected StableRetirementEligibility $eligibility,
        protected TagTeamRetirementEligibility $tagTeamRetirementEligibility,
        protected StableMembershipService $membershipService,
    ) {}

    /**
     * Retire a stable and end its operations.
     *
     * This handles the complete stable retirement workflow:
     * - Validates the stable can be retired (business rule compliance)
     * - Ends current wrestler and tag team memberships at the operational date
     * - Retires eligible former members and closes their remaining relationships at the retirement date
     * - Ends the activity period if currently active
     * - Creates the retirement record
     * - Makes the stable permanently unavailable for storylines
     * - Preserves historical activity and membership records
     *
     * @param  Stable  $stable  The stable to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     * @throws CannotBeRetiredException When stable cannot be retired due to business rules
     */
    public function handle(Stable $stable, ?Carbon $retirementDate = null): void
    {
        $retirementDate = $retirementDate ?? now();
        $operationalDate = $retirementDate->isFuture() ? now() : $retirementDate;

        DB::transaction(function () use ($stable, $retirementDate, $operationalDate): void {
            $lockedStable = Stable::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($stable->getKey());

            $this->eligibility->ensureCanRetire($lockedStable);

            if ($lockedStable->isCurrentlyActive()) {
                $this->endActivityPeriodAction->handle($lockedStable, $operationalDate);
            }

            $currentMembers = $this->membershipService->currentMembers($lockedStable);
            $this->removeStableMembersAction->handle($lockedStable, $currentMembers, $operationalDate);

            if ($currentMembers->wrestlers) {
                foreach ($currentMembers->wrestlers as $wrestler) {
                    if ($this->individualRetirementEligibility->canRetire($wrestler)) {
                        $this->wrestlersRetireAction->handle($wrestler, $retirementDate);
                    }
                }
            }

            if ($currentMembers->tagTeams) {
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
