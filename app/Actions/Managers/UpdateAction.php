<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Data\ManagerData;
use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Update a manager.
     */
    public function handle(Manager $manager, ManagerData $managerData): Manager
    {
        $this->managerRepository->update($manager, $managerData);

        if (! is_null($managerData->start_date) && $this->shouldBeEmployed($manager)) {
            $this->managerRepository->employ($manager, $managerData->start_date);
        }

        return $manager;
    }

    /**
     * Find out if the manager can be employed.
     */
    private function shouldBeEmployed(Manager $manager): bool
    {
        if ($manager->isCurrentlyEmployed()) {
            return false;
        }

        return true;
    }
}
