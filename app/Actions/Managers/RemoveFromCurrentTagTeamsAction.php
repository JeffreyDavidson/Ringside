<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

class RemoveFromCurrentTagTeamsAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Remove manager from currently managed tag teams.
     *
     * @param  \App\Models\Manager  $manager
     * @return void
     */
    public function handle(Manager $manager): void
    {
        $this->managerRepository->removeFromCurrentTagTeams($manager);
    }
}
