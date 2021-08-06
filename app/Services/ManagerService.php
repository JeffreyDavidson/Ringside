<?php

namespace App\Services;

use App\Models\Manager;
use App\Repositories\ManagerRepository;
use App\Strategies\ClearInjury\ManagerClearInjuryStrategy;
use App\Strategies\Employment\ManagerEmploymentStrategy;
use App\Strategies\Injure\ManagerInjuryStrategy;
use App\Strategies\Reinstate\ManagerReinstateStrategy;
use App\Strategies\Release\ManagerReleaseStrategy;
use App\Strategies\Retirement\ManagerRetirementStrategy;
use App\Strategies\Suspend\ManagerSuspendStrategy;
use App\Strategies\Unretire\ManagerUnretireStrategy;
use Carbon\Carbon;

class ManagerService
{
    /**
     * The repository implementation.
     *
     * @var \App\Repositories\ManagerRepository
     */
    protected $managerRepository;

    /**
     * Create a new manger service instance.
     *
     * @param \App\Repositories\ManagerRepository $managerRepository
     */
    public function __construct(ManagerRepository $managerRepository)
    {
        $this->managerRepository = $managerRepository;
    }

    /**
     * Create a manager.
     *
     * @param  array $data
     * @return \App\Models\Manager $manager
     */
    public function create(array $data)
    {
        $manager = $this->managerRepository->create($data);

        if ($data['started_at']) {
            (new ManagerEmploymentStrategy($manager))->employ(Carbon::parse($data['started_at']));
        }

        return $manager;
    }

    /**
     * Update a manager.
     *
     * @param  \App\Models\Manager $manager
     * @param  array $data
     * @return \App\Models\Manager $manager
     */
    public function update(Manager $manager, array $data)
    {
        $this->managerRepository->update($manager, $data);

        if ($data['started_at']) {
            $this->employOrUpdateEmployment($manager, Carbon::parse($data['started_at']));
        }

        return $manager;
    }

    public function employOrUpdateEmployment(Manager $manager, $startedAt)
    {
        if ($manager->isUnemployed()) {
            (new ManagerEmploymentStrategy($manager))->employ($startedAt);
        }

        if ($manager->hasFutureEmployment() && $manager->futureEmployment->started_at->ne($startedAt)) {
            return $manager->futureEmployment()->update(['started_at' => $startedAt]);
        }
    }

    /**
     * Clear an injury of a manager.
     *
     * @param  \App\Models\Manager $manager
     * @return void
     */
    public function clearFromInjury(Manager $manager)
    {
        (new ManagerClearInjuryStrategy($manager))->clearInjury();
    }

    /**
     * Injure a manager.
     *
     * @param  \App\Models\Manager $manager
     * @return void
     */
    public function injure(Manager $manager)
    {
        (new ManagerInjuryStrategy($manager))->injure();
    }

    /**
     * Employ a manager.
     *
     * @param  \App\Models\Manager $manager
     * @return void
     */
    public function employ(Manager $manager)
    {
        (new ManagerEmploymentStrategy($manager))->employ();
    }

    /**
     * Employ a manager.
     *
     * @param  \App\Models\Manager $manager
     * @return void
     */
    public function reinstate(Manager $manager)
    {
        (new ManagerReinstateStrategy($manager))->reinstate();
    }

    /**
     * Unretire a manager.
     *
     * @param  \App\Models\Manager $manager
     * @return void
     */
    public function unretire(Manager $manager)
    {
        (new ManagerUnretireStrategy($manager))->unretire();
    }

    /**
     * Unretire a manager.
     *
     * @param  \App\Models\Manager $manager
     * @return void
     */
    public function suspend(Manager $manager)
    {
        (new ManagerSuspendStrategy($manager))->suspend();
    }

    /**
     * Retire a manager.
     *
     * @param  \App\Models\Manager $manager
     * @return void
     */
    public function retire(Manager $manager)
    {
        (new ManagerRetirementStrategy($manager))->retire();
    }

    /**
     * Release a manager.
     *
     * @param  \App\Models\Manager $manager
     * @return void
     */
    public function release(Manager $manager)
    {
        (new ManagerReleaseStrategy($manager))->release();
    }
}
