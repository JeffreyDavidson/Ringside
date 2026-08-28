<?php

declare(strict_types=1);

namespace App\Actions\Lifecycle;

use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Models\Contracts\HasActivityPeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class EndActivityPeriodAction
{
    /** @param Model&HasActivityPeriods<covariant Model> $activeable */
    public function handle(Model&HasActivityPeriods $activeable, Carbon $endedAt): void
    {
        $context = class_basename($activeable).' activity';

        if ($endedAt->isFuture()) {
            throw InvalidDateRangeException::futureNotAllowed($endedAt, $context.' end');
        }

        DB::transaction(function () use ($activeable, $endedAt, $context): void {
            $lockedActiveable = $activeable->refreshForUpdate();

            if (! $lockedActiveable instanceof HasActivityPeriods) {
                throw new LogicException(class_basename($activeable).' does not support activity periods.');
            }

            $currentActivityPeriod = $lockedActiveable->activityPeriods()
                ->whereNull('ended_at')
                ->where('started_at', '<=', now())
                ->lockForUpdate()
                ->first();

            if (! $currentActivityPeriod) {
                $activeableKey = $activeable->getKey();
                $activeableIdentifier = is_int($activeableKey) || is_string($activeableKey)
                    ? $activeableKey
                    : 'unknown';

                throw new LogicException(class_basename($activeable)." {$activeableIdentifier} does not have a current activity period.");
            }

            if ($endedAt->lt($currentActivityPeriod->started_at)) {
                throw InvalidDateRangeException::endBeforeStart(
                    $currentActivityPeriod->started_at,
                    $endedAt,
                    $context,
                );
            }

            $currentActivityPeriod->update(['ended_at' => $endedAt]);
        });
    }
}
