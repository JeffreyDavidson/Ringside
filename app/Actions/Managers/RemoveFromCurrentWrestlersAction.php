<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Helpers\DateHelper;
use App\Models\Managers\Manager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RemoveFromCurrentWrestlersAction
{
    use AsAction;

    /**
     * Remove a manager from all currently managed wrestlers.
     *
     * This handles the complete wrestler management removal workflow:
     * - Ends all current wrestler management relationships for the manager
     * - Uses the specified removal date or defaults to now
     * - Preserves historical management records for tracking purposes
     * - Maintains referential integrity with wrestler management history
     *
     * BUSINESS IMPACT:
     * - Wrestlers lose their current manager but retain management history
     * - Manager becomes available for assignment to other wrestlers
     * - No impact on wrestler employment or match availability
     * - Management statistics and historical records remain intact
     *
     * @param  Manager  $manager  The manager to remove from wrestlers
     * @param  Carbon|null  $removalDate  The removal date (defaults to now)
     *
     * @example
     * ```php
     * // Remove manager from all wrestlers immediately
     * RemoveFromCurrentWrestlersAction::run($manager);
     *
     * // Remove with specific date for record keeping
     * RemoveFromCurrentWrestlersAction::run($manager, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $removalDate = null): void
    {
        $removalDate = DateHelper::resolveDate($removalDate);

        DB::transaction(function () use ($manager, $removalDate): void {
            // More efficient: get IDs first, then update in one operation
            $currentWrestlerIds = $manager->wrestlers()
                ->wherePivotNull('fired_at')
                ->pluck('wrestler_id')
                ->toArray();

            if (! empty($currentWrestlerIds)) {
                $manager->wrestlers()->updateExistingPivot($currentWrestlerIds, [
                    'fired_at' => $removalDate,
                ]);
            }
        });
    }
}
