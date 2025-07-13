<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\ReinstateAction as ManagersReinstateAction;
use App\Actions\Wrestlers\ReinstateAction as WrestlersReinstateAction;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Models\TagTeams\TagTeam;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Create a new reinstate action instance.
     */
    public function __construct(
        protected TagTeamRepository $tagTeamRepository,
        protected WrestlersReinstateAction $wrestlersReinstateAction,
        protected ManagersReinstateAction $managersReinstateAction
    ) {
        parent::__construct($tagTeamRepository);
    }

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
     *
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

        $reinstatementDate = $this->getEffectiveDate($reinstatementDate);

        DB::transaction(function () use ($tagTeam, $reinstatementDate): void {
            $this->tagTeamRepository->endSuspension($tagTeam, $reinstatementDate);

            // Reinstate suspended wrestlers and managers who were suspended with this team
            $wrestlersToReinstate = $tagTeam->currentWrestlers
                ->filter(fn ($wrestler) => $wrestler->isSuspended());
            $managersToReinstate = $tagTeam->currentManagers
                ->filter(fn ($manager) => $manager->isSuspended());

            $this->reinstateMembers($wrestlersToReinstate, $managersToReinstate, $reinstatementDate, $this->wrestlersReinstateAction, $this->managersReinstateAction);
        });
    }
}
