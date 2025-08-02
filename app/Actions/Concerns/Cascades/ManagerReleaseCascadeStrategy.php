<?php

declare(strict_types=1);

namespace App\Actions\Concerns\Cascades;

use App\Models\Managers\Manager;
use Illuminate\Support\Carbon;

class ManagerReleaseCascadeStrategy
{
    /**
     * End all management relationships when a manager is released.
     *
     * When a manager is released from employment, all their current management
     * contracts should be terminated as they are no longer able to fulfill
     * their management duties.
     *
     * @return callable Strategy for ending management relationships on release
     */
    public static function endManagementContracts(): callable
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
     * Comprehensive release cascade handling all necessary cleanup.
     *
     * @return callable Complete release cascade strategy
     */
    public static function comprehensive(): callable
    {
        return self::endManagementContracts();
    }
}
