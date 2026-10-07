<?php

declare(strict_types=1);

namespace App\Actions\Lifecycle;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Models\Contracts\HasActivityPeriods;
use App\Support\ModelKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class EndActivityPeriodAction
{
    public function __construct(private readonly RecordLifecycleTransitionAction $recordLifecycleTransition) {}

    /**
     * Close the current activity period, recording the given transition (when any) in the same transaction.
     *
     * @param  Model&HasActivityPeriods<covariant Model>  $activeable
     * @param  array<string, mixed>  $context  Metadata stored on the recorded transition
     */
    public function handle(
        Model&HasActivityPeriods $activeable,
        Carbon $endedAt,
        ?LifecycleTransitionType $transition = null,
        array $context = [],
    ): void {
        $errorContext = class_basename($activeable).' activity';

        if ($endedAt->isFuture()) {
            throw InvalidDateRangeException::futureNotAllowed($endedAt, $errorContext.' end');
        }

        DB::transaction(function () use ($activeable, $endedAt, $transition, $errorContext, $context): void {
            $lockedActiveable = $activeable->refreshForUpdate();

            $currentActivityPeriod = $lockedActiveable->activityPeriods()
                ->whereNull('ended_at')
                ->where('started_at', '<=', now())
                ->lockForUpdate()
                ->first();

            if (! $currentActivityPeriod) {
                $activeableKey = ModelKey::of($activeable);

                throw new LogicException(class_basename($activeable)." {$activeableKey} does not have a current activity period.");
            }

            if ($endedAt->lt($currentActivityPeriod->started_at)) {
                throw InvalidDateRangeException::endBeforeStart(
                    $currentActivityPeriod->started_at,
                    $endedAt,
                    $errorContext,
                );
            }

            $currentActivityPeriod->update(['ended_at' => $endedAt]);

            if ($transition instanceof LifecycleTransitionType) {
                $this->recordLifecycleTransition->handle($lockedActiveable, LifecycleDimension::Activity, $transition, $endedAt, $context);
            }
        });
    }
}
