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
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Data\ManagerData  $managerData
     * @return \App\Models\Manager
     */
    public function handle(Manager $manager, ManagerData $managerData): Manager
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
}
