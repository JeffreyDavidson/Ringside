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
     *
     * @throws \App\Exceptions\CannotBeInjuredException
     */
    public function handle(Manager $manager, ?Carbon $injureDate = null): void
    {
        throw_if($manager->isInjured(), CannotBeInjuredException::class, $manager.' is currently injured and cannot be injured further.');

        $injureDate ??= now();

        $this->managerRepository->injure($manager, $injureDate);
    }
}
