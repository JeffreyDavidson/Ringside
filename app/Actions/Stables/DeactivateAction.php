<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeactivateAction
{
    use AsAction;

    /**
     * Deactivate a stable.
     *
     * @throws CannotBeDisbandedException
     */
    public function handle(Stable $stable, ?Carbon $deactivationDate = null): void
    {
        $this->ensureCanBeDeactivated($stable);

        $deactivationDate = $deactivationDate ?? now();

        DB::transaction(function () use ($stable, $deactivationDate): void {
            // End current activity period
            $currentActivityPeriod = $stable->currentActivityPeriod()->first();
            if ($currentActivityPeriod) {
                $currentActivityPeriod->update(['ended_at' => $deactivationDate]);
            }

            // End all current member tenures
            $stable->currentWrestlers()->updateExistingPivot(
                $stable->currentWrestlers()->pluck('wrestler_id'),
                ['left_at' => $deactivationDate]
            );
            $stable->currentTagTeams()->updateExistingPivot(
                $stable->currentTagTeams()->pluck('tag_team_id'),
                ['left_at' => $deactivationDate]
            );
        });
    }

    /**
     * Ensure a stable can be deactivated.
     *
     * @throws CannotBeDisbandedException
     */
    private function ensureCanBeDeactivated(Stable $stable): void
    {
        if ($stable->isUnactivated()) {
            throw CannotBeDisbandedException::unactivated($stable);
        }

        if (! $stable->isCurrentlyActive()) {
            throw CannotBeDisbandedException::disbanded($stable);
        }

        if ($stable->hasFutureActivation()) {
            throw CannotBeDisbandedException::hasFutureActivation($stable);
        }

        if ($stable->isRetired()) {
            throw CannotBeDisbandedException::retired($stable);
        }
    }
}
