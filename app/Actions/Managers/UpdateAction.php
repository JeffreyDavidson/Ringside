<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Data\Managers\ManagerData;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    public function __construct(
        private EmployAction $employAction
    ) {}

    /**
     * Update a manager.
     *
     * This handles the complete manager update workflow:
     * - Updates manager personal and professional information
     * - Uses EmployAction for consistent employment handling if employment_date is modified
     * - Maintains data integrity throughout the update process
     *
     * ARCHITECTURAL PATTERN:
     * Uses EmployAction for employment handling, following the same pattern as other
     * manager actions for consistency.
     *
     * @param  Manager  $manager  The manager to update
     * @param  ManagerData  $managerData  The updated manager information
     * @return Manager The updated manager instance
     */
    public function handle(Manager $manager, ManagerData $managerData): Manager
    {
        return DB::transaction(function () use ($manager, $managerData): Manager {
            $lockedManager = $manager->refreshForUpdate();

            $lockedManager->update([
                'first_name' => $managerData->first_name,
                'last_name' => $managerData->last_name,
            ]);

            // Handle employment using EmployAction for consistency
            if (! is_null($managerData->employment_date) && ! $lockedManager->isEmployed()) {
                $this->employAction->handle($lockedManager, $managerData->employment_date);
            }

            return $lockedManager;
        });
    }
}
