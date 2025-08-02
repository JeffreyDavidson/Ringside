<?php

declare(strict_types=1);

namespace App\Actions\Concerns\Cascades;

use App\Models\Managers\Manager;
use Illuminate\Support\Carbon;

class ManagerDeletionCascadeStrategy
{
    /**
     * End all management relationships when a manager is deleted.
     *
     * This strategy handles the comprehensive cleanup of manager relationships:
     * - Ends all current wrestler management contracts
     * - Ends all current tag team management contracts
     * - Preserves historical management records for reporting
     * - Uses efficient bulk operations to minimize database queries
     *
     * @return callable Strategy for ending all management relationships
     */
    public static function endAllManagementRelationships(): callable
    {
        return function (Manager $manager, Carbon $effectiveDate): void {
            // End current wrestler management relationships
            $currentWrestlerIds = $manager->wrestlers()
                ->wherePivotNull('fired_at')
                ->pluck('wrestler_id')
                ->toArray();

            if (! empty($currentWrestlerIds)) {
                $manager->wrestlers()->updateExistingPivot($currentWrestlerIds, [
                    'fired_at' => $effectiveDate,
                ]);
            }

            // End current tag team management relationships
            $currentTagTeamIds = $manager->tagTeams()
                ->wherePivotNull('fired_at')
                ->pluck('tag_team_id')
                ->toArray();

            if (! empty($currentTagTeamIds)) {
                $manager->tagTeams()->updateExistingPivot($currentTagTeamIds, [
                    'fired_at' => $effectiveDate,
                ]);
            }
        };
    }

    /**
     * Comprehensive deletion cascade that handles all manager relationships.
     *
     * This is the primary cascade strategy for manager deletion, combining
     * all necessary cleanup operations in a single efficient strategy.
     *
     * @return callable Complete deletion cascade strategy
     */
    public static function comprehensive(): callable
    {
        return self::endAllManagementRelationships();
    }
}
