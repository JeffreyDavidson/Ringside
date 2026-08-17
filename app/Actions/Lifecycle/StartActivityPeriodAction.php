<?php

declare(strict_types=1);

namespace App\Actions\Lifecycle;

use App\Models\Contracts\HasActivityPeriods;
use App\Models\Lifecycle\ActivityPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class StartActivityPeriodAction
{
    /** @param Model&HasActivityPeriods<covariant Model> $activeable */
    public function handle(
        Model&HasActivityPeriods $activeable,
        Carbon $startedAt,
        bool $rescheduleFuturePeriod = false,
    ): ActivityPeriod {
        return DB::transaction(function () use ($activeable, $startedAt, $rescheduleFuturePeriod): ActivityPeriod {
            $lockedActiveable = $activeable->newQueryWithoutScopes()
                ->whereKey($activeable->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedActiveable instanceof HasActivityPeriods) {
                throw new LogicException(class_basename($activeable).' does not support activity periods.');
            }

            $openActivityPeriod = $lockedActiveable->activityPeriods()
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($rescheduleFuturePeriod && $openActivityPeriod?->started_at->isFuture()) {
                $openActivityPeriod->update(['started_at' => $startedAt]);

                return $openActivityPeriod;
            }

            if ($openActivityPeriod) {
                $activeableKey = $activeable->getKey();
                $activeableIdentifier = is_int($activeableKey) || is_string($activeableKey)
                    ? $activeableKey
                    : 'unknown';

                throw new LogicException(class_basename($activeable)." {$activeableIdentifier} already has an open activity period.");
            }

            return $lockedActiveable->activityPeriods()->create([
                'started_at' => $startedAt,
            ]);
        });
    }
}
