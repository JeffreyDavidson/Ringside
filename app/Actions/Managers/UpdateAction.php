<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Data\Managers\ManagerData;
use App\Helpers\DateHelper;
use App\Models\Managers\Manager;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction
{
    use AsAction;

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
     *
     * @example
     * ```php
     * // Update manager information only
     * $managerData = new ManagerData([
     *     'name' => 'Updated Name',
     *     'hometown' => 'New Hometown'
     * ]);
     * $updatedManager = UpdateAction::run($manager, $managerData);
     *
     * // Update and employ an unemployed manager
     * $managerData = new ManagerData([
     *     'name' => 'Triple H',
     *     'employment_date' => Carbon::parse('2024-01-01')
     * ]);
     * $updatedManager = UpdateAction::run($unemployedManager, $managerData);
     * ```
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
