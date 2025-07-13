<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Data\Managers\ManagerData;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseManagerAction
{
    use AsAction;

    public function __construct(
        ManagerRepository $managerRepository
    ) {
        parent::__construct($managerRepository);
    }

    /**
     * Create a manager.
     *
     * This handles the complete manager creation workflow:
     * - Creates the manager record with personal and professional details
     * - Creates employment record if employment_date is provided
     * - Establishes the manager as available for talent management
     *
     * @param  ManagerData  $managerData  The data transfer object containing manager information
     * @return Manager The newly created manager instance
     *
     * @example
     * ```php
     * // Create manager with immediate employment
     * $managerData = new ManagerData([
     *     'name' => 'Paul Heyman',
     *     'hometown' => 'New York, NY',
     *     'employment_date' => now()
     * ]);
     * $manager = CreateAction::run($managerData);
     *
     * // Create manager without employment (must be employed separately)
     * $managerData = new ManagerData([
     *     'name' => 'Stephanie McMahon',
     *     'hometown' => 'Greenwich, CT'
     * ]);
     * $manager = CreateAction::run($managerData);
     * ```
     */
    public function handle(ManagerData $managerData): Manager
    {
        return DB::transaction(function () use ($managerData): Manager {
            // Create the base manager record
            $manager = $this->managerRepository->create($managerData);

            // Create employment record if employment_date is provided
            if (isset($managerData->employment_date)) {
                $this->managerRepository->createEmployment($manager, $managerData->employment_date);
            }

            return $manager;
        });
    }
}
