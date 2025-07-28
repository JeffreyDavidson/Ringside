<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * End the current activity period for a stable.
 *
 * This is a focused action that handles the specific task of closing
 * an active stable's activity period with a specified end date.
 */
class EndActivityPeriodAction
{
    use AsAction;

    /**
     * End the current activity period for a stable.
     *
     * @param  Stable  $stable  The stable to end activity for
     * @param  Carbon  $endDate  The date to end the activity period
     */
    public function handle(Stable $stable, Carbon $endDate): void
    {
        $currentActivityPeriod = $stable->currentActivityPeriod()->first();

        if ($currentActivityPeriod) {
            $currentActivityPeriod->update(['ended_at' => $endDate]);
        }
    }
}
