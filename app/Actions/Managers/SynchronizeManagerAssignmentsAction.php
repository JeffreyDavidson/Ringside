<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Contracts\Manageable;
use App\Models\Roster\Managers\Manager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SynchronizeManagerAssignmentsAction
{
    public function __construct(protected AssignManagersAction $assignManagersAction) {}

    /**
     * @param  Manageable<*, *>  $manageable
     * @param  Collection<int, Manager>|null  $managers
     */
    public function handle(Manageable $manageable, ?Collection $managers, Carbon $date): void
    {
        if (! $managers instanceof Collection) {
            return;
        }

        $currentManagers = $manageable->currentManagers()->get();

        foreach ($currentManagers->diff($managers) as $manager) {
            $manageable->managers()->newPivotStatementForId($manager->getKey())
                ->whereNull('fired_at')
                ->update(['fired_at' => $date]);
        }

        $this->assignManagersAction->handle($manageable, $managers->diff($currentManagers), $date);
    }
}
