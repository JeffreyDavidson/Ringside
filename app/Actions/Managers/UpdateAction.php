<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Data\Managers\ManagerData;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
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
            // Update the manager's basic information
            $manager->update([
                'first_name' => $managerData->first_name,
                'last_name' => $managerData->last_name,
            ]);

            // Handle employment using EmployAction for consistency
            if (! is_null($managerData->employment_date) && ! $manager->isEmployed()) {
                $employmentDate = DateHelper::resolveDate($managerData->employment_date);
                $this->employAction->handle($manager, $employmentDate);
            }

            return $manager;
        });
    }
}
