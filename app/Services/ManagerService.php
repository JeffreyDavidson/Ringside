<?php

namespace App\Services;

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
     * @param  array $data
     * @return \App\Models\Manager $manager
     */
    public function create(array $data)
    {
        $manager = $this->managerRepository->create($data);

        if (isset($data['started_at'])) {
            $this->managerRepository->employ($manager, $data['started_at']);
        }

        return $manager;
    }

    /**
     * Update a given manager with given data.
     *
     * @param  \App\Models\Manager $manager
     * @param  array $data
     * @return \App\Models\Manager $manager
     */
    public function update(Manager $manager, array $data)
    {
        $this->managerRepository->update($manager, $data);

        if ($manager->canHaveEmploymentStartDateChanged() && isset($data['started_at'])) {
            $this->employOrUpdateEmployment($manager, $data['started_at']);
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
