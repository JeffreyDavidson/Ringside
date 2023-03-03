<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\CannotBeInjuredException;
use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class InjureAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Injure a manager.
     */
    public function handle(Manager $manager, ?Carbon $injureDate = null): void
    {
        $this->ensureCanBeInjured($manager);

        $injureDate ??= now();

        $this->managerRepository->injure($manager, $injureDate);
    }

    /**
     * Ensure a manager can be injured.
     *
     * @throws \App\Exceptions\CannotBeInjuredException
     */
    private function ensureCanBeInjured(Manager $manager): void
    {
        if ($manager->isUnemployed()) {
            throw CannotBeInjuredException::unemployed($manager);
        }

        if ($manager->isReleased()) {
            throw CannotBeInjuredException::released($manager);
        }

        if ($manager->isRetired()) {
            throw CannotBeInjuredException::retired($manager);
        }

        if ($manager->hasFutureEmployment()) {
            throw CannotBeInjuredException::hasFutureEmployment($manager);
        }

        if ($manager->isInjured()) {
            throw CannotBeInjuredException::injured($manager);
        }

        if ($manager->isSuspended()) {
            throw CannotBeInjuredException::suspended($manager);
        }
    }
}
