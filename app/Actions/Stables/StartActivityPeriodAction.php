<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stables\Stable;
use App\Models\Stables\StableActivityPeriod;
use Illuminate\Support\Carbon;

class StartActivityPeriodAction
{
    public function handle(Stable $stable, Carbon $startedAt): StableActivityPeriod
    {
        return $stable->activityPeriods()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $startedAt->toDateTimeString()],
        );
    }
}
