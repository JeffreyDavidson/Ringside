<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Managers\RetireAction as ManagersRetireAction;
use App\Actions\TagTeams\RetireAction as TagTeamsRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Enums\Stables\StableStatus;
use App\Exceptions\Roster\Stables\CannotBeRetiredException;
use App\Lifecycle\RetirementPeriodManager;
use App\Models\Stables\Stable;
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
        protected ManagersRetireAction $managersRetireAction,
        protected EndActivityPeriodAction $endActivityPeriodAction,
        protected RemoveStableMembersAction $removeStableMembersAction,
        protected RetirementPeriodManager $retirementPeriods,
    ) {}

    /**
     * Retire a stable and end its operations.
     *
     * This handles the complete stable retirement workflow with flexible options:
     * - Validates the stable can be retired (business rule compliance)
     * - Basic retirement: Ends stable operations, members become free agents
     * - With member retirement: Also retires available members simultaneously
     * - Forced retirement: Overrides business rule conflicts (admin use)
     * - Ends current wrestler memberships (wrestlers may continue as singles/other stables)
     * - Ends current tag team memberships (tag teams may continue independently)
     * - Ends current manager relationships (managers may continue with other talent)
     * - Ends debut period if currently active
     * - Creates retirement record with optional reason metadata
     * - Makes the stable permanently unavailable for storylines
     * - Preserves all historical records and championship lineage
     * - Individual members may continue their careers independently
     *
     * @param  Stable  $stable  The stable to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     * @throws CannotBeRetiredException When stable cannot be retired due to business rules
     */
    public function handle(Stable $stable, ?Carbon $retirementDate = null): void
    {
        $stable->ensureCanBeRetired();

        $retirementDate = $retirementDate ?? now();
        $operationalDate = $retirementDate->isFuture() ? now() : $retirementDate;

        DB::transaction(function () use ($stable, $retirementDate, $operationalDate): void {
            // End activity if currently active using injected Action
            if ($stable->isCurrentlyActive()) {
                $this->endActivityPeriodAction->handle($stable, $operationalDate);
            }

            // Get current members using enhanced model method
            $currentMembers = $stable->getCurrentMembersData();

            // Retire current members who are available
            $membersToRetire = $stable->getMembersToRetire();

            if ($membersToRetire->wrestlers) {
                foreach ($membersToRetire->wrestlers as $wrestler) {
                    if ($wrestler->canBeRetired()) {
                        $this->wrestlersRetireAction->handle($wrestler, $retirementDate);
                    }
                }
            }

            if ($membersToRetire->tagTeams) {
                foreach ($membersToRetire->tagTeams as $tagTeam) {
                    if ($tagTeam->canBeRetired()) {
                        $this->tagTeamsRetireAction->handle($tagTeam, $retirementDate);
                    }
                }
            }

            // Remove all current members using injected Action
            $this->removeStableMembersAction->handle($stable, $currentMembers, $operationalDate);

            $this->retirementPeriods->start($stable, $retirementDate);

            // Update status to retired
            $stable->update(['status' => StableStatus::Retired]);
        });
    }
}
