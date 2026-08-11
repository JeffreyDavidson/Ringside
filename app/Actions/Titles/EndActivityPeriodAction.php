<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class EndActivityPeriodAction
{
    public function handle(Title $title, Carbon $endedAt): void
    {
        if ($endedAt->isFuture()) {
            throw InvalidDateRangeException::futureNotAllowed($endedAt, 'Title activity end');
        }

        DB::transaction(function () use ($title, $endedAt): void {
            $lockedTitle = Title::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($title->getKey());

            $openActivityPeriod = $lockedTitle->activityPeriods()
                ->whereNull('ended_at')
                ->where('started_at', '<=', now())
                ->lockForUpdate()
                ->first();

            if (! $openActivityPeriod) {
                throw new LogicException("Title {$lockedTitle->getKey()} does not have a current activity period.");
            }

            if ($endedAt->lt($openActivityPeriod->started_at)) {
                throw InvalidDateRangeException::endBeforeStart(
                    $openActivityPeriod->started_at,
                    $endedAt,
                    'title activity',
                );
            }

            $openActivityPeriod->update(['ended_at' => $endedAt]);
        });
    }
}
