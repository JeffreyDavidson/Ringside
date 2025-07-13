<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Managers\RetireAction as ManagersRetireAction;
use App\Actions\TagTeams\RetireAction as TagTeamsRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Stables\Stable;
use App\Repositories\StableRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseStableAction
{
    use AsAction;

    /**
     * Create a new retire action instance.
     */
    public function __construct(
        protected StableRepository $stableRepository,
        protected WrestlersRetireAction $wrestlersRetireAction,
        protected TagTeamsRetireAction $tagTeamsRetireAction,
        protected ManagersRetireAction $managersRetireAction
    ) {
        parent::__construct($stableRepository);
    }

    /**
     * Retire a stable and end its operations.
     *
     * This handles the complete stable retirement workflow with cascading effects:
     * - Validates the stable can be retired (currently active/debuted)
     * - Ends current wrestler memberships (wrestlers may continue as singles/other stables)
     * - Ends current tag team memberships (tag teams may continue independently)
     * - Ends current manager relationships (managers may continue with other talent)
     * - Ends debut period if currently active
     * - Creates retirement record to formally end the stable's existence
     * - Makes the stable permanently unavailable for storylines
     * - Preserves all historical records and championship lineage
     * - Individual members may continue their careers independently
     *
     * @param  Stable  $stable  The stable to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     *
     * @throws CannotBeRetiredException When stable cannot be retired due to business rules
     *
     * @example
     * ```php
     * // Retire stable immediately
     * RetireAction::run($stable);
     *
     * // Retire with specific date
     * RetireAction::run($stable, Carbon::parse('2024-12-31'));
     *
     * // Retire The New World Order stable
     * $nwo = Stable::where('name', 'The New World Order')->first();
     * RetireAction::run($nwo, Carbon::parse('2024-04-01'));
     * ```
     */
    public function handle(Stable $stable, ?Carbon $retirementDate = null): void
    {
        $stable->ensureCanBeRetired();

        $retirementDate = $this->getEffectiveDate($retirementDate);

        DB::transaction(function () use ($stable, $retirementDate): void {
            // End activity if currently active
            if ($stable->isCurrentlyActive()) {
                $this->stableRepository->endActivity($stable, $retirementDate);
            }

            // Retire current members who are available
            // Note: Managers are not direct stable members and are not retired with the stable
            $wrestlersToRetire = $stable->currentWrestlers
                ->filter(fn ($wrestler) => ! $wrestler->isRetired());
            $tagTeamsToRetire = $stable->currentTagTeams
                ->filter(fn ($tagTeam) => ! $tagTeam->isRetired());

            $this->retireMembers(
                $wrestlersToRetire,
                $tagTeamsToRetire,
                collect(), // Empty collection for managers since they're not direct members
                $retirementDate,
                $this->wrestlersRetireAction,
                $this->tagTeamsRetireAction,
                $this->managersRetireAction
            );

            // End current memberships
            // Note: Managers are not direct stable members, so we don't remove them
            $this->stableRepository->removeWrestlers($stable, $stable->currentWrestlers, $retirementDate);
            $this->stableRepository->removeTagTeams($stable, $stable->currentTagTeams, $retirementDate);

            // Create retirement record for the stable
            $this->stableRepository->createRetirement($stable, $retirementDate);
        });
    }
}
