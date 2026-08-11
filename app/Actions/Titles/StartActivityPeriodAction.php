<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use App\Models\Titles\TitleActivityPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class StartActivityPeriodAction
{
    public function handle(Title $title, Carbon $startedAt): TitleActivityPeriod
    {
        return DB::transaction(function () use ($title, $startedAt): TitleActivityPeriod {
            $lockedTitle = Title::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($title->getKey());

            $openActivityPeriod = $lockedTitle->activityPeriods()
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($openActivityPeriod && $openActivityPeriod->started_at->isFuture()) {
                $openActivityPeriod->update(['started_at' => $startedAt]);

                return $openActivityPeriod;
            }

            if ($openActivityPeriod) {
                throw new LogicException("Title {$lockedTitle->getKey()} already has an open activity period.");
            }

            return $lockedTitle->activityPeriods()->create([
                'started_at' => $startedAt,
            ]);
        });
    }
}
