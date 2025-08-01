<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Managers\Manager;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RemoveFromCurrentWrestlersAction
{
    use AsAction;

    public function handle(Manager $manager, ?Carbon $removalDate = null): void
    {
        $removalDate = DateHelper::resolveDate($removalDate);

        $manager->wrestlers()->wherePivotNull('fired_at')->updateExistingPivot(
            $manager->wrestlers()->wherePivotNull('fired_at')->pluck('wrestler_id'),
            ['fired_at' => $removalDate]
        );
    }
}
