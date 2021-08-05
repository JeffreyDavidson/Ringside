<?php

namespace App\Services;

use App\Models\Manager;
use App\Repositories\ManagerRepository;

class ManagerService
{
    protected $managerRepository;

    public function __construct(ManagerRepository $managerRepository)
    {
        $this->managerRepository = $managerRepository;
    }

    /**
     * Creates a new tag team.
     *
     * @param  array $data
     * @return \App\Models\Manager $manager
     */
    public function create(array $data): Manager
    {
        $manager = $this->managerRepository->create($data);

        if ($data['started_at']) {
            $this->employ($manager, $data['started_at']);
        }

        return $manager;
    }

    /**
     * Updates a new manager.
     *
     * @param  \App\Models\Manager $manager
     * @param  array $data
     * @return \App\Models\Manager $manager
     */
    public function update(Manager $manager, array $data): Manager
    {
        $manager->update([
            'name' => $data['name'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'hometown' => $data['hometown'],
            'signature_move' => $data['signature_move'],
        ]);

        if ($data['started_at']) {
            $this->employOrUpdateEmployment($manager, $data['started_at']);
        }

        return $manager;
    }

    public function employOrUpdateEmployment(Manager $manager, $startedAt)
    {
        if ($manager->isUnemployed()) {
            return $this->employ($manager, $startedAt);
        }

        if ($manager->hasFutureEmployment() && $manager->futureEmployment->started_at->ne($startedAt)) {
            return $manager->futureEmployment()->update(['started_at' => $startedAt]);
        }
    }
}
