<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableMembershipData;
use App\Exceptions\Status\CannotBeDisbandedException;
use App\Models\Stables\Stable;
use App\Services\StableMembershipService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DisbandAction
{
    use AsAction;

    /**
     * Disband a stable.
     *
     * This handles the complete stable disbandment workflow:
     * - Validates the stable can be disbanded (currently active)
     * - Ends the stable's activity period to mark it as disbanded
     * - Removes all current members from the stable
     * - Preserves historical membership records
     * - Members remain available for other opportunities
     *
     * @param  Stable  $stable  The stable to disband
     * @param  Carbon|null  $disbandDate  The disbandment date (defaults to now)
     * @throws CannotBeDisbandedException If the stable cannot be disbanded
     *
     * @example
     * ```php
     * $stable = Stable::find(1);
     * DisbandAction::run($stable, now());
     * ```
     */
    public function handle(Stable $stable, ?Carbon $disbandDate = null): void
    {
        $stable->ensureCanBeDisbanded();

        $disbandDate = $disbandDate ?? now();

        DB::transaction(function () use ($stable, $disbandDate): void {
            // End current activity period
            $currentActivityPeriod = $stable->currentActivityPeriod()->first();
            if ($currentActivityPeriod) {
                $currentActivityPeriod->update(['ended_at' => $disbandDate]);
            }

            // End all current member tenures using service
            if ($stable->currentWrestlers->isNotEmpty() || $stable->currentTagTeams->isNotEmpty()) {
                $currentMembers = new StableMembershipData(
                    wrestlers: $stable->currentWrestlers,
                    tagTeams: $stable->currentTagTeams
                );

                $membershipService = app(StableMembershipService::class);
                $membershipService->removeMembers($stable, $currentMembers, $disbandDate);
            }
        });
    }
}
