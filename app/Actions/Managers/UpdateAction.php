<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Data\ManagerData;
use App\Models\Manager;
use Illuminate\Support\Carbon;
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

        if ($this->shouldBeEmployed($manager, $managerData->start_date)) {
            $this->managerRepository->employ($manager, $managerData->start_date);
        }

        return $manager;
    }

    /**
     * Find out if the manager can be employed.
     */
    private function shouldBeEmployed(Manager $manager, ?Carbon $startDate): bool
    {
        if (is_null($startDate)) {
            return false;
        }

        if ($manager->isCurrentlyEmployed()) {
            return false;
        }

        return true;
    }
}
