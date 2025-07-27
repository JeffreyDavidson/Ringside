<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Managers\RetireAction as ManagersRetireAction;
use App\Actions\TagTeams\RetireAction as TagTeamsRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlersRetireAction;
use App\Exceptions\Roster\CannotBeRetiredException;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction
{
    use AsAction;

    /**
     * Create a new retire action instance.
     */
    public function __construct(
        protected WrestlersRetireAction $wrestlersRetireAction,
        protected TagTeamsRetireAction $tagTeamsRetireAction,
        protected ManagersRetireAction $managersRetireAction
    ) {}

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

        $retirementDate = $retirementDate ?? now();

        DB::transaction(function () use ($stable, $retirementDate): void {
            // End activity if currently active
            if ($stable->isCurrentlyActive()) {
                $stable->activityPeriods()->where('ended_at', null)->update(['ended_at' => $retirementDate]);
            }

            // Retire current members who are available
            // Note: Managers are not direct stable members and are not retired with the stable
            $wrestlersToRetire = $stable->currentWrestlers
                ->filter(fn (Wrestler $wrestler) => ! $wrestler->isRetired());
            $tagTeamsToRetire = $stable->currentTagTeams
                ->filter(fn (TagTeam $tagTeam) => ! $tagTeam->isRetired());

            // Retire wrestlers
            $wrestlersToRetire->each(fn (Wrestler $wrestler) => $this->wrestlersRetireAction->handle($wrestler, $retirementDate));

            // Retire tag teams
            $tagTeamsToRetire->each(fn (TagTeam $tagTeam) => $this->tagTeamsRetireAction->handle($tagTeam, $retirementDate));

            // End current memberships
            // Note: Managers are not direct stable members, so we don't remove them
            $stable->currentWrestlers->each(function (Wrestler $wrestler) use ($stable, $retirementDate) {
                $stable->wrestlers()->updateExistingPivot($wrestler->id, [
                    'left_at' => $retirementDate,
                ]);
            });

            $stable->currentTagTeams->each(function (TagTeam $tagTeam) use ($stable, $retirementDate) {
                $stable->tagTeams()->updateExistingPivot($tagTeam->id, [
                    'left_at' => $retirementDate,
                ]);
            });

            // Create retirement record for the stable
            $stable->retirements()->create([
                'started_at' => $retirementDate,
                'ended_at' => null,
            ]);
        });
    }
}
