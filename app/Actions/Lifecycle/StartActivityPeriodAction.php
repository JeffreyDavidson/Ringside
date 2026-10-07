<?php

declare(strict_types=1);

namespace App\Actions\Lifecycle;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\HasActivityPeriods;
use App\Models\Lifecycle\ActivityPeriod;
use App\Support\ModelKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class StartActivityPeriodAction
{
    public function __construct(private readonly RecordLifecycleTransitionAction $recordLifecycleTransition) {}

    /**
     * Open an activity period, recording the given transition (when any) in the same transaction.
     *
     * @param  Model&HasActivityPeriods<covariant Model>  $activeable
     * @param  array<string, mixed>  $context  Metadata stored on the recorded transition
     */
    public function handle(
        Model&HasActivityPeriods $activeable,
        Carbon $startedAt,
        bool $rescheduleFuturePeriod = false,
        ?LifecycleTransitionType $transition = null,
        array $context = [],
    ): ActivityPeriod {
        return DB::transaction(function () use ($activeable, $startedAt, $rescheduleFuturePeriod, $transition, $context): ActivityPeriod {
            $lockedActiveable = $activeable->refreshForUpdate();

            $openActivityPeriod = $lockedActiveable->activityPeriods()
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($rescheduleFuturePeriod && $openActivityPeriod?->started_at->isFuture()) {
                $openActivityPeriod->update(['started_at' => $startedAt]);
                $this->recordTransition($lockedActiveable, $transition, $startedAt, $context);

                return $openActivityPeriod;
            }

            if ($openActivityPeriod) {
                $activeableKey = ModelKey::of($activeable);

                throw new LogicException(class_basename($activeable)." {$activeableKey} already has an open activity period.");
            }

            $activityPeriod = $lockedActiveable->activityPeriods()->create([
                'started_at' => $startedAt,
            ]);

            $this->recordTransition($lockedActiveable, $transition, $startedAt, $context);

            return $activityPeriod;
        });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function recordTransition(
        Model $activeable,
        ?LifecycleTransitionType $transition,
        Carbon $date,
        array $context,
    ): void {
        if (! $transition instanceof LifecycleTransitionType) {
            return;
        }

        $this->recordLifecycleTransition->handle($activeable, LifecycleDimension::Activity, $transition, $date, $context);
    }
}
