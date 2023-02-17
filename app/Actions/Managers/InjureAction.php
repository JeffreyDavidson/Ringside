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
     *
     * @throws \App\Exceptions\CannotBeInjuredException
     */
    public function handle(Manager $manager, ?Carbon $injureDate = null): void
    {
        throw_if($manager->isInjured(), CannotBeInjuredException::class, $manager.' is currently injured and cannot be injured further.');
        throw_if($manager->isUnemployed(), CannotBeInjuredException::class, $manager.' is currently unemployed and cannot be injured.');
        throw_if($manager->isSuspended(), CannotBeInjuredException::class, $manager.' is currently suspended and cannot be injured.');
        throw_if($manager->isReleased(), CannotBeInjuredException::class, $manager.' is currently released and cannot be injured.');
        throw_if($manager->hasFutureEmployment(), CannotBeInjuredException::class, $manager.' is has a future employment and cannot be injured.');
        throw_if($manager->isRetired(), CannotBeInjuredException::class, $manager.' is currently retired and cannot be injured.');

        $injureDate ??= now();

        $this->managerRepository->injure($manager, $injureDate);
    }
}
