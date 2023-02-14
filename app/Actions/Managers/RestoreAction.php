<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Delete a manager.
     */
    public function handle(Manager $manager): void
    {
        $this->managerRepository->restore($manager);
    }
}
