<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Contracts\Manageable;
use App\Models\Roster\Managers\Manager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class AssignManagersAction
{
    /**
     * @param  Manageable<*, *>  $manageable
     * @param  Collection<int, Manager>|null  $managers
     */
    public function handle(Manageable $manageable, ?Collection $managers, Carbon $date): void
    {
        if ($managers === null || $managers->isEmpty()) {
            return;
        }

        $manageable->managers()->attach($managers->modelKeys(), [
            'hired_at' => $date,
            'fired_at' => null,
        ]);
    }
}
