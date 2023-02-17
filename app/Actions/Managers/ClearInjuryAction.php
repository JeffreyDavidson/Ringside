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
     * @throws \App\Exceptions\CannotBeClearedFromInjuryException
     */
    public function handle(Manager $manager, ?Carbon $recoveryDate = null): void
    {
        throw_if(! $manager->isInjured(), CannotBeClearedFromInjuryException::class, $manager.' is not currently injured and cannot be cleared from an injury.');

        $recoveryDate ??= now();

        $this->managerRepository->clearInjury($manager, $recoveryDate);
    }
}
