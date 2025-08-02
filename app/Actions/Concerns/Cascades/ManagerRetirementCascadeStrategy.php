<?php

declare(strict_types=1);

namespace App\Actions\Concerns\Cascades;

use App\Models\Managers\Manager;
use Illuminate\Support\Carbon;

class ManagerRetirementCascadeStrategy
{
    /**
     * End all management relationships when a manager retires.
     *
     * When a manager retires, all their current management relationships
     * should be ended as they are no longer active in talent management.
     * This preserves the historical record while making managed entities
     * available for new management assignments.
     *
     * @return callable Strategy for ending management relationships on retirement
     */
    public static function endManagementCareer(): callable
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
     * Comprehensive retirement cascade handling all necessary cleanup.
     *
     * @return callable Complete retirement cascade strategy
     */
    public static function comprehensive(): callable
    {
        return self::endManagementCareer();
    }
}
