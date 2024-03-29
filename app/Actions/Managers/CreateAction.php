<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Data\ManagerData;
use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Create a manager.
     */
    public function handle(ManagerData $managerData): Manager
    {
        /** @var Manager $manager */
        $manager = $this->managerRepository->create($managerData);

        if (isset($managerData->start_date)) {
            $this->managerRepository->employ($manager, $managerData->start_date);
        }

        return $manager;
    }
}
