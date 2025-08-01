<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Managers\EmployAction as ManagersEmployAction;
use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for handling wrestler-manager assignment operations.
 *
 * This service centralizes the logic for assigning managers to wrestlers,
 * including ensuring managers are properly employed when needed.
 */
class WrestlerManagerAssignmentService
{
    public function __construct(
        private ManagersEmployAction $managersEmployAction
    ) {}

    /**
     * Assign managers to a wrestler with proper employment handling.
     *
     * This method:
     * - Attaches managers to the wrestler with appropriate pivot data
     * - Ensures all assigned managers are employed
     * - Uses consistent datetime for all operations
     *
     * @param  Wrestler  $wrestler  The wrestler to assign managers to
     * @param  Collection<int, Manager>  $managers  The managers to assign
     * @param  Carbon  $assignmentDate  The date of the assignment
     */
    public function assignManagersToWrestler(Wrestler $wrestler, Collection $managers, Carbon $assignmentDate): void
    {
        foreach ($managers as $manager) {
            // Attach manager to wrestler with proper pivot data
            $wrestler->managers()->attach($manager->id, [
                'hired_at' => $assignmentDate,
                'fired_at' => null,
            ]);

            // Ensure manager is employed
            if (! $manager->isEmployed()) {
                $this->managersEmployAction->handle($manager, $assignmentDate);
            }
        }
    }
}
