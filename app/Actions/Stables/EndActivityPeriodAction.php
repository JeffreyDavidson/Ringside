<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\BusinessRules\InvalidDateRangeException;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * End the current activity period for a stable.
 *
 * This is a focused action that handles the specific task of closing
 * an active stable's activity period with a specified end date.
 */
class EndActivityPeriodAction
{
    /**
     * End the current activity period for a stable.
     *
     * @param  Stable  $stable  The stable to end activity for
     * @param  Carbon  $endDate  The date to end the activity period
     * @throws InvalidDateRangeException When the end date is outside the valid period range
     * @throws LogicException When the stable has no open activity period
     */
    public function handle(Stable $stable, Carbon $endDate): void
    {
        if ($endDate->isFuture()) {
            throw InvalidDateRangeException::futureNotAllowed($endDate, 'Stable activity end');
        }

        DB::transaction(function () use ($stable, $endDate): void {
            $lockedStable = Stable::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($stable->getKey());

            $currentActivityPeriod = $lockedStable->activityPeriods()
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if (! $currentActivityPeriod) {
                throw new LogicException("Stable {$lockedStable->getKey()} does not have an open activity period.");
            }

            if ($endDate->lt($currentActivityPeriod->started_at)) {
                throw InvalidDateRangeException::endBeforeStart(
                    $currentActivityPeriod->started_at,
                    $endDate,
                    'stable activity',
                );
            }

            $currentActivityPeriod->update(['ended_at' => $endDate]);
        });
    }
}
