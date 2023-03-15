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

        if (isset($managerData->start_date)) {
            if ($manager->canBeEmployed()
                || $manager->canHaveEmploymentStartDateChanged($managerData->start_date)
            ) {
                $this->managerRepository->employ($manager, $managerData->start_date);
            }
        }

        return $manager;
    }
}
