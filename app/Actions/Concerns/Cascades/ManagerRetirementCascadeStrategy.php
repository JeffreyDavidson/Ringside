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
            $manager->removeFromCurrentWrestlers($effectiveDate);
            $manager->removeFromCurrentTagTeams($effectiveDate);
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
