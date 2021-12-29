<?php

namespace App\Services;

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
     * @return \App\Models\Manager $manager
     */
    public function create(ManagerData $managerData)
    {
        $manager = $this->managerRepository->create($managerData);

        if (isset($managerData->start_date)) {
            $this->managerRepository->employ($manager, $managerData->start_date);
        }

        return $manager;
    }

    /**
     * Update a given manager with given data.
     *
     * @param  \App\Models\Manager $manager
     * @param  \App\DataTransferObjects\ManagerData $managerData
     * @return \App\Models\Manager $manager
     */
    public function update(Manager $manager, ManagerData $managerData)
    {
        $this->managerRepository->update($manager, $managerData);

        if ($manager->canHaveEmploymentStartDateChanged() && isset($managerData->start_date)) {
            $this->employOrUpdateEmployment($manager, $managerData->start_date);
        }

        return $manager;
    }

    /**
     * Employ a given manager or update the given manager's employment date.
     *
     * @param  \App\Models\Manager $manager
     * @param  string $employmentDate
     * @return void
     */
    private function employOrUpdateEmployment(Manager $manager, string $employmentDate)
    {
        if ($manager->isNotInEmployment()) {
            return $this->managerRepository->employ($manager, $employmentDate);
        }

        if ($manager->hasFutureEmployment() && ! $manager->employedOn($employmentDate)) {
            return $this->managerRepository->updateEmployment($manager, $employmentDate);
        }
    }

    /**
     * Delete a given manager.
     *
     * @param  \App\Models\Manager $manager
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
     * @return void
     */
    public function restore(Manager $manager)
    {
        $this->managerRepository->restore($manager);
    }
}
