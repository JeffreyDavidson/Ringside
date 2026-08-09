<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Contracts\Manageable;
use App\Models\Managers\Manager;
use Illuminate\Support\Carbon;

class EmployCurrentManagersAction
{
    public function __construct(private readonly EmployAction $employManager) {}

    /**
     * @param  Manageable<*, *>  $manageable
     */
    public function handle(Manageable $manageable, Carbon $employmentDate): void
    {
        $managers = $manageable->currentManagers()
            ->get()
            ->filter(fn (Manager $manager) => ! $manager->isEmployed() && ! $manager->hasFutureEmployment());

        foreach ($managers as $manager) {
            $this->employManager->handle($manager, $employmentDate);
        }
    }
}
