<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

final class RemoveFromCurrentWrestlersAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Remove manager from currently managed wrestlers.
     */
    public function handle(Manager $manager): void
    {
        $this->managerRepository->removeFromCurrentWrestlers($manager);
    }
}
