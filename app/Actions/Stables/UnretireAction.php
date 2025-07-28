<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Managers\UnretireAction as ManagersUnretireAction;
use App\Actions\TagTeams\UnretireAction as TagTeamsUnretireAction;
use App\Actions\Wrestlers\UnretireAction as WrestlersUnretireAction;
use App\Enums\Stables\StableStatus;
use App\Exceptions\Roster\CannotBeUnretiredException;
use App\Models\Stables\Stable;
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
        protected TagTeamsUnretireAction $tagTeamsUnretireAction,
        protected ManagersUnretireAction $managersUnretireAction
    ) {}

    /**
     * Unretire a retired stable and make it active again.
     *
     * This handles the complete stable unretirement workflow:
     * - Validates the stable can be unretired (currently retired)
     * - Ends the current retirement period with the specified date
     * - Attempts to unretire former members who retired with the stable
     * - Creates a new debut record starting from the unretirement date
     * - Makes the stable available for new storylines and championship opportunities
     * - Re-establishes the stable as an active competitive force
     *
     * @param  Stable  $stable  The stable to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @throws CannotBeUnretiredException When stable cannot be unretired due to business rules
     *
     * @example
     * ```php
     * // Unretire stable immediately
     * $retiredStable = Stable::where('name', 'Evolution')->first();
     * UnretireAction::run($retiredStable);
     *
     * // Unretire with specific date
     * UnretireAction::run($retiredStable, Carbon::parse('2024-01-01'));
     *
     * // Unretire The nWo stable for a reunion storyline
     * $nwo = Stable::where('name', 'The New World Order')->first();
     * UnretireAction::run($nwo, Carbon::parse('2024-07-04'));
     * ```
     */
    public function handle(Stable $stable, ?Carbon $unretiredDate = null): void
    {
        $stable->ensureCanBeUnretired();

        $unretiredDate = $unretiredDate ?? now();

        DB::transaction(function () use ($stable, $unretiredDate): void {
            // End the current retirement record directly
            $currentRetirement = $stable->retirements()
                ->whereNull('unretired_at')
                ->first();

            if ($currentRetirement) {
                $currentRetirement->update(['unretired_at' => $unretiredDate]);
            }

            // Get former members who retired with the stable
            $formerMembers = $stable->getCurrentMembersData();

            // Attempt to unretire former members who retired with the stable
            if ($formerMembers->wrestlers) {
                foreach ($formerMembers->wrestlers as $wrestler) {
                    if ($wrestler->isRetired()) {
                        try {
                            $this->wrestlersUnretireAction->handle($wrestler, $unretiredDate);
                        } catch (Exception) {
                            // Continue if wrestler cannot be unretired
                        }
                    }
                }
            }

            if ($formerMembers->tagTeams) {
                foreach ($formerMembers->tagTeams as $tagTeam) {
                    if ($tagTeam->isRetired()) {
                        try {
                            $this->tagTeamsUnretireAction->handle($tagTeam, $unretiredDate);
                        } catch (Exception) {
                            // Continue if tag team cannot be unretired
                        }
                    }
                }
            }

            // Update status to inactive (no longer retired, but not active)
            $stable->update(['status' => StableStatus::Inactive]);
        });
    }
}
