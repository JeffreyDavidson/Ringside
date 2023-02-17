<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\CannotBeUnretiredException;
use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Unretire a manager.
     *
     * @throws \App\Exceptions\CannotBeUnretiredException
     */
    public function handle(Manager $manager, ?Carbon $unretiredDate = null): void
    {
        throw_if(! $manager->isRetired(), CannotBeUnretiredException::class, $manager.' is not retired so cannot be unretired.');

        $unretiredDate ??= now();

        $this->managerRepository->unretire($manager, $unretiredDate);

        EmployAction::run($manager, $unretiredDate);
    }
}
