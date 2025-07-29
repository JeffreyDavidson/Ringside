<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\UnretireAction as ManagersUnretireAction;
use App\Actions\Wrestlers\UnretireAction as WrestlersUnretireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction
{
    use AsAction;

    /**
     * Create a new unretire action instance.
     */
    public function __construct(
        protected WrestlersUnretireAction $wrestlersUnretireAction,
        protected ManagersUnretireAction $managersUnretireAction,
        protected EmployAction $employAction
    ) {}

    /**
     * Unretire a retired tag team and return them to active competition.
     *
     * This handles the complete tag team comeback workflow with flexible options:
     * - Validates the tag team can come out of retirement (business rule compliance)
     * - Ends the current retirement period with the specified date
     * - Optionally unretires available partners and managers
     * - Optionally employs the team immediately or leaves unemployed for manual employment
     * - Flexible partner requirements for different unretirement scenarios
     * - Restores the tag team to available status for match bookings
     * - Makes the team available for championship opportunities again
     * - Preserves all historical retirement and partnership records
     *
     * @param  TagTeam  $tagTeam  The tag team to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @param  bool  $unretirePartners  Whether to unretire available partners (default: true)
     * @param  bool  $employImmediately  Whether to employ the team immediately (default: true)
     * @param  bool  $requireAvailablePartners  Whether to require available partners (default: true)
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
     *
     * // Unretire without employing immediately (manual employment later)
     * UnretireAction::run($tagTeam, employImmediately: false);
     *
     * // Unretire without requiring available partners
     * UnretireAction::run($tagTeam, requireAvailablePartners: false);
     *
     * // Unretire without unretiring partners (team only)
     * UnretireAction::run($tagTeam, unretirePartners: false);
     * ```
     */
    public function handle(
        TagTeam $tagTeam,
        ?Carbon $unretiredDate = null,
        bool $unretirePartners = true,
        bool $employImmediately = true,
        bool $requireAvailablePartners = true
    ): void {
        $tagTeam->ensureCanBeUnretired($requireAvailablePartners);

        $unretiredDate = $unretiredDate ?? now();

        DB::transaction(function () use ($tagTeam, $unretiredDate, $unretirePartners, $employImmediately): void {
            // End the current retirement record
            $tagTeam->retirements()->where('ended_at', null)->update(['ended_at' => $unretiredDate]);

            // Unretire current partners and managers if requested
            if ($unretirePartners) {
                $wrestlersToUnretire = $tagTeam->currentWrestlers
                    ->filter(fn (Wrestler $wrestler) => $wrestler->isRetired());
                $managersToUnretire = $tagTeam->currentManagers
                    ->filter(fn (Manager $manager) => $manager->isRetired());

                // Unretire available wrestlers
                foreach ($wrestlersToUnretire as $wrestler) {
                    try {
                        $this->wrestlersUnretireAction->handle($wrestler, $unretiredDate);
                    } catch (Exception $e) {
                        // Continue if wrestler cannot be unretired
                    }
                }

                // Unretire available managers
                foreach ($managersToUnretire as $manager) {
                    try {
                        $this->managersUnretireAction->handle($manager, $unretiredDate);
                    } catch (Exception $e) {
                        // Continue if manager cannot be unretired
                    }
                }
            }

            // Update status to unemployed (no longer retired, but not employed)
            $tagTeam->update(['status' => EmploymentStatus::Unemployed]);

            // Employ immediately if requested
            if ($employImmediately) {
                $this->employAction->handle($tagTeam, $unretiredDate);
            }
        });
    }
}
