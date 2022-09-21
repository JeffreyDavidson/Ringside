<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\CannotBeClearedFromInjuryException;
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
     * @param  \Illuminate\Support\Carbon|null  $recoveryDate
     * @return void
     *
     * @throws \App\Exceptions\CannotBeClearedFromInjuryException
     */
    public function handle(Manager $manager, ?Carbon $recoveryDate = null): void
    {
        throw_if($manager->canBeClearedFromInjury(), CannotBeClearedFromInjuryException::class);

        $recoveryDate ??= now();

        $this->managerRepository->clearInjury($manager, $recoveryDate);
    }
}
