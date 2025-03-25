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
        $this->ensureCanBeClearedFromInjury($manager);

        $recoveryDate ??= now();

        $this->managerRepository->clearInjury($manager, $recoveryDate);
    }

    /**
     * Ensure a manager can be cleared from an injury.
     *
     * @throws \App\Exceptions\CannotBeClearedFromInjuryException
     */
    private function ensureCanBeClearedFromInjury(Manager $manager): void
    {
        if (! $manager->isInjured()) {
            throw CannotBeClearedFromInjuryException::notInjured();
        }
    }
}
