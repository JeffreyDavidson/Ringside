<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\ReinstateAction as ManagersReinstateAction;
use App\Actions\Wrestlers\ReinstateAction as WrestlersReinstateAction;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction
{
    use AsAction;

    /**
     * Create a new reinstate action instance.
     */
    public function __construct(
        protected WrestlersReinstateAction $wrestlersReinstateAction,
        protected ManagersReinstateAction $managersReinstateAction
    ) {}

    /**
     * Reinstate a suspended tag team.
     *
     * This handles the complete tag team reinstatement workflow:
     * - Validates the tag team can be reinstated (currently suspended)
     * - Ends the current suspension period with the specified date
     * - Restores the tag team to active competition status
     * - Makes the team available for match bookings and championships again
     * - Individual members who were suspended separately need separate reinstatement
     *
     * @param  TagTeam  $tagTeam  The tag team to reinstate
     * @param  Carbon|null  $reinstatementDate  The reinstatement date (defaults to now)
     * @throws CannotBeReinstatedException When tag team cannot be reinstated due to business rules
     *
     * @example
     * ```php
     * // Reinstate tag team immediately
     * $tagTeam = TagTeam::where('name', 'The Usos')->first();
     * ReinstateAction::run($tagTeam);
     *
     * // Reinstate with specific date
     * ReinstateAction::run($tagTeam, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $reinstatementDate = null): void
    {
        $tagTeam->ensureCanBeReinstated();

        $reinstatementDate = $reinstatementDate ?? now();

        DB::transaction(function () use ($tagTeam, $reinstatementDate): void {
            $tagTeam->suspensions()->where('ended_at', null)->update(['ended_at' => $reinstatementDate]);

            // Reinstate suspended wrestlers and managers who were suspended with this team
            $wrestlersToReinstate = $tagTeam->currentWrestlers
                ->filter(fn (Wrestler $wrestler) => $wrestler->isSuspended());
            $managersToReinstate = $tagTeam->currentManagers
                ->filter(fn (Manager $manager) => $manager->isSuspended());

            // Reinstate the provided wrestlers
            $wrestlersToReinstate->each(fn (Wrestler $wrestler) => $this->wrestlersReinstateAction->handle($wrestler, $reinstatementDate));

            // Reinstate the provided managers
            $managersToReinstate->each(fn (Manager $manager) => $this->managersReinstateAction->handle($manager, $reinstatementDate));
        });
    }
}
