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
     * @param  \App\Models\Manager  $manager
     * @param  \Illuminate\Support\Carbon|null  $unretiredDate
     * @return void
     *
     * @throws \App\Exceptions\CannotBeUnretiredException
     */
    public function handle(Manager $manager, ?Carbon $unretiredDate = null): void
    {
        throw_unless($manager->canBeUnretired(), CannotBeUnretiredException::class);

        $unretiredDate ??= now();

        $this->managerRepository->unretire($manager, $unretiredDate);

        EmployAction::run($manager, $unretiredDate);
    }
}
