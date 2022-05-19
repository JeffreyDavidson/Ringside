<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ClearInjuryAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Clear an injury of a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon|null $recoveryDate
     * @return void
     */
    public function handle(Manager $manager, ?Carbon $recoveryDate = null): void
    {
        $recoveryDate ??= now();

        $this->managerRepository->clearInjury($manager, $recoveryDate);
        $manager->save();
    }
}
