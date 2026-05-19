<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Data\Managers\ManagerData;
use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction
{
    use AsAction;

    public function __construct(
        private EmployAction $employAction
    ) {}

    /**
     * Create a manager.
     *
     * This handles the complete manager creation workflow:
     * - Creates the manager record with personal and professional details
     * - Uses EmployAction for consistent employment handling if employment_date is provided
     * - Establishes the manager as available for talent management
     *
     * ARCHITECTURAL PATTERN:
     * Uses EmployAction for employment handling, following the same pattern as other
     * manager actions for consistency.
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
            /** @var Manager $manager */
            $manager = Manager::query()->create([
                'first_name' => $managerData->first_name,
                'last_name' => $managerData->last_name,
            ]);

            // Handle employment using EmployAction for consistency
            if (! is_null($managerData->employment_date)) {
                $employmentDate = DateHelper::resolveDate($managerData->employment_date);
                $this->employAction->handle($manager, $employmentDate);
            }

            return $manager;
        });
    }
}
