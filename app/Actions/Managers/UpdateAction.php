<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Data\Managers\ManagerData;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseManagerAction
{
    use AsAction;

    public function __construct(
        ManagerRepository $managerRepository
    ) {
        parent::__construct($managerRepository);
    }

    /**
     * Update a manager.
     *
     * This handles the complete manager update workflow:
     * - Updates manager personal and professional information
     * - Handles conditional employment if employment_date is modified
     * - Maintains data integrity throughout the update process
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
            $this->managerRepository->update($manager, $managerData);

            // Create employment record if employment_date is provided and manager is eligible
            if (! is_null($managerData->employment_date) && ! $manager->isEmployed()) {
                $this->managerRepository->createEmployment($manager, $managerData->employment_date);
            }

            return $manager;
        });
    }
}
