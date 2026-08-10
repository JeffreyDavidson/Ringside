<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contracts\Manageable;
use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ManagerAssignmentService
{
    /**
     * @param  Manageable<*, *>  $manageable
     * @param  Collection<int, Manager>|null  $managers
     */
    public function assign(Manageable $manageable, ?Collection $managers, Carbon $date): void
    {
        if ($managers === null || $managers->isEmpty()) {
            return;
        }

        $manageable->managers()->attach($managers->modelKeys(), [
            'hired_at' => $date,
            'fired_at' => null,
        ]);
    }

    /**
     * @param  Manageable<*, *>  $manageable
     * @param  Collection<int, Manager>|null  $managers
     */
    public function synchronize(Manageable $manageable, ?Collection $managers, Carbon $date): void
    {
        if ($managers === null) {
            return;
        }

        $currentManagers = $manageable->currentManagers()->get();

        foreach ($currentManagers->diff($managers) as $manager) {
            $manageable->managers()->updateExistingPivot($manager->getKey(), [
                'fired_at' => $date,
            ]);
        }

        $this->assign($manageable, $managers->diff($currentManagers), $date);
    }
}
