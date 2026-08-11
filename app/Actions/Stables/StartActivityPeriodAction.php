<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stables\Stable;
use App\Models\Stables\StableActivityPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class StartActivityPeriodAction
{
    public function handle(Stable $stable, Carbon $startedAt): StableActivityPeriod
    {
        return DB::transaction(function () use ($stable, $startedAt): StableActivityPeriod {
            $lockedStable = Stable::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($stable->getKey());

            if ($lockedStable->activityPeriods()->whereNull('ended_at')->exists()) {
                throw new LogicException("Stable {$lockedStable->getKey()} already has an open activity period.");
            }

            return $lockedStable->activityPeriods()->create([
                'started_at' => $startedAt,
            ]);
        });
    }
}
