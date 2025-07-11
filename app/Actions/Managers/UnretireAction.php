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
     * @throws CannotBeUnretiredException
     */
    public function handle(Manager $manager, ?Carbon $unretiredDate = null): void
    {
        $this->ensureCanBeUnretired($manager);

        $unretiredDate ??= now();

        $this->managerRepository->unretire($manager, $unretiredDate);
        $this->managerRepository->employ($manager, $unretiredDate);
    }

    /**
     * Ensure a manager can be unretired.
     *
     * @throws CannotBeUnretiredException
     */
    private function ensureCanBeUnretired(Manager $manager): void
    {
        if (! $manager->isRetired()) {
            throw CannotBeUnretiredException::notRetired();
        }
    }
}
