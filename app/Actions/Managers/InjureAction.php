<?php

namespace App\Actions\Managers;

use App\Models\Manager;
use Lorisleiva\Actions\Concerns\AsAction;

class InjureAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Injure a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return void
     */
    public function handle(Manager $manager): void
    {
        $injureDate = now()->toDateTimeString();

        $this->managerRepository->injure($manager, $injureDate);
        $manager->save();
    }
}
