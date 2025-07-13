<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\UnretireAction as ManagersUnretireAction;
use App\Actions\Wrestlers\UnretireAction as WrestlersUnretireAction;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\TagTeams\TagTeam;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Create a new unretire action instance.
     */
    public function __construct(
        protected TagTeamRepository $tagTeamRepository,
        protected WrestlersUnretireAction $wrestlersUnretireAction,
        protected ManagersUnretireAction $managersUnretireAction
    ) {
        parent::__construct($tagTeamRepository);
    }

    /**
     * Unretire a retired tag team and return them to active competition.
     *
     * This handles the complete tag team comeback workflow:
     * - Validates the tag team can come out of retirement (currently retired)
     * - Ends the current retirement period with the specified date
     * - Creates a new employment record starting from the unretirement date
     * - Restores the tag team to available status for match bookings
     * - Makes the team available for championship opportunities again
     * - Preserves all historical retirement and partnership records
     * - Unretires current wrestlers and managers who were retired with the team
     *
     * @param  TagTeam  $tagTeam  The tag team to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     *
     * @throws CannotBeUnretiredException When tag team cannot be unretired due to business rules
     *
     * @example
     * ```php
     * // Unretire tag team immediately
     * $tagTeam = TagTeam::where('name', 'The Hardy Boyz')->first();
     * UnretireAction::run($tagTeam);
     *
     * // Unretire with specific date
     * UnretireAction::run($tagTeam, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $unretiredDate = null): void
    {
        $tagTeam->ensureCanBeUnretired();

        $unretiredDate = $this->getEffectiveDate($unretiredDate);

        DB::transaction(function () use ($tagTeam, $unretiredDate): void {
            // End the current retirement record
            $this->tagTeamRepository->endRetirement($tagTeam, $unretiredDate);

            // Unretire current wrestlers and managers who were retired with the team
            $wrestlersToUnretire = $tagTeam->currentWrestlers
                ->filter(fn ($wrestler) => $wrestler->isRetired());
            $managersToUnretire = $tagTeam->currentManagers
                ->filter(fn ($manager) => $manager->isRetired());

            $this->unretireMembers($wrestlersToUnretire, $managersToUnretire, $unretiredDate, $this->wrestlersUnretireAction, $this->managersUnretireAction);

            // Create a new employment record starting from the unretirement date
            $this->tagTeamRepository->createEmployment($tagTeam, $unretiredDate);
        });
    }
}
