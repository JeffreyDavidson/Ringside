<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\CannotBeDeactivatedException;
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
     * @throws CannotBeDeactivatedException
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
            $stable->currentWrestlerTenures()->update(['left_at' => $deactivationDate]);
            $stable->currentTagTeamTenures()->update(['left_at' => $deactivationDate]);
        });
    }

    /**
     * Ensure a stable can be deactivated.
     *
     * @throws CannotBeDeactivatedException
     */
    private function ensureCanBeDeactivated(Stable $stable): void
    {
        if ($stable->isUnactivated()) {
            throw CannotBeDeactivatedException::unactivated();
        }

        if (! $stable->isCurrentlyActive()) {
            throw CannotBeDeactivatedException::deactivated();
        }

        if ($stable->hasFutureActivation()) {
            throw CannotBeDeactivatedException::hasFutureActivation();
        }

        if ($stable->isRetired()) {
            throw CannotBeDeactivatedException::retired();
        }
    }
}
