<?php

namespace App\Services;

use App\Actions\Managers\EmployAction;
use App\DataTransferObjects\ManagerData;
use App\Models\Manager;
use App\Repositories\ManagerRepository;

class ManagerService
{
    /**
     * The repository implementation.
     *
     * @var \App\Repositories\ManagerRepository
     */
    protected $managerRepository;

    /**
     * Create a new manager service instance.
     *
     * @param \App\Repositories\ManagerRepository $managerRepository
     */
    public function __construct(ManagerRepository $managerRepository)
    {
        $this->managerRepository = $managerRepository;
    }

    /**
     * Create a manager with given data.
     *
     * @param  \App\DataTransferObjects\ManagerData $managerData
     *
     * @return \App\Models\Manager $manager
     */
    public function create(ManagerData $managerData)
    {
        /* @var \App\Models\Manager $manager */
        $manager = $this->managerRepository->create($managerData);

        if (isset($managerData->start_date)) {
            EmployAction::run($manager, $managerData->start_date);
        }

        return $manager;
    }

    /**
     * Update a given manager with given data.
     *
     * @param  \App\Models\Manager $manager
     * @param  \App\DataTransferObjects\ManagerData $managerData
     *
     * @return \App\Models\Manager $manager
     */
    public function update(Manager $manager, ManagerData $managerData)
    {
        $this->managerRepository->update($manager, $managerData);

        if (isset($managerData->start_date)) {
            if ($manager->canBeEmployed()
                || $manager->canHaveEmploymentStartDateChanged($managerData->start_date)
            ) {
                EmployAction::run($manager, $managerData->start_date);
            }
        }

        return $manager;
    }

    /**
     * Delete a given manager.
     *
     * @param  \App\Models\Manager $manager
     *
     * @return void
     */
    public function delete(Manager $manager)
    {
        $this->managerRepository->delete($manager);
    }

    /**
     * Restore a given manager.
     *
     * @param  \App\Models\Manager $manager
     *
     * @return void
     */
    public function restore(Manager $manager)
    {
        $this->managerRepository->restore($manager);
    }
}
