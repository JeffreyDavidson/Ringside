<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RemoveFromCurrentTagTeamsAction
{
    use AsAction;

    /**
     * Remove a manager from all currently managed tag teams.
     *
     * This handles the complete tag team management removal workflow:
     * - Ends all current tag team management relationships for the manager
     * - Uses the specified removal date or defaults to now
     * - Preserves historical management records for tracking purposes
     * - Maintains referential integrity with tag team management history
     *
     * BUSINESS IMPACT:
     * - Tag teams lose their current manager but retain management history
     * - Manager becomes available for assignment to other tag teams
     * - No impact on tag team employment or match availability
     * - Management statistics and historical records remain intact
     *
     * @param  Manager  $manager  The manager to remove from tag teams
     * @param  Carbon|null  $removalDate  The removal date (defaults to now)
     *
     * @example
     * ```php
     * // Remove manager from all tag teams immediately
     * RemoveFromCurrentTagTeamsAction::run($manager);
     *
     * // Remove with specific date for record keeping
     * RemoveFromCurrentTagTeamsAction::run($manager, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(Manager $manager, ?Carbon $removalDate = null): void
    {
        $removalDate = DateHelper::resolveDate($removalDate);

        DB::transaction(function () use ($manager, $removalDate): void {
            // More efficient: get IDs first, then update in one operation
            $currentTagTeamIds = $manager->tagTeams()
                ->wherePivotNull('fired_at')
                ->pluck('tag_team_id')
                ->toArray();

            if (! empty($currentTagTeamIds)) {
                $manager->tagTeams()->updateExistingPivot($currentTagTeamIds, [
                    'fired_at' => $removalDate,
                ]);
            }
        });
    }
}
