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
            $manager->wrestlers()->terminateActive($effectiveDate);
            $manager->tagTeams()->terminateActive($effectiveDate);
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
