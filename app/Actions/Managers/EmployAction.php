<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Exceptions\CannotBeEmployedException;
use App\Models\Manager;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseManagerAction
{
    use AsAction;

    /**
     * Employ a manager.
     */
    public function handle(Manager $manager, ?Carbon $startDate = null): void
    {
        $this->ensureCanBeEmployed($manager);

        $startDate ??= now();

        $this->managerRepository->employ($manager, $startDate);
    }

    /**
     * Ensure a manager can be employed.
     *
     * @throws \App\Exceptions\CannotBeEmployedException
     */
    private function ensureCanBeEmployed(Manager $manager): void
    {
        if ($manager->isCurrentlyEmployed()) {
            throw CannotBeEmployedException::employed($manager);
        }
    }
}
