<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class LifecyclePeriodWriter
{
    public function __construct(private RecordLifecycleTransitionAction $recordLifecycleTransition) {}

    /**
     * @param  MorphMany<*, *>  $periods
     */
    public function start(
        Model $subject,
        MorphMany $periods,
        LifecycleDimension $dimension,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        $this->runWithinTransaction(function () use ($subject, $periods, $dimension, $date, $transition): void {
            $periods->create([
                'started_at' => $date,
                'ended_at' => null,
            ]);

            $this->recordTransition($subject, $dimension, $transition, $date);
        });
    }

    /**
     * @param  MorphOne<*, *>  $currentPeriod
     */
    public function end(
        Model $subject,
        MorphOne $currentPeriod,
        LifecycleDimension $dimension,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        $this->runWithinTransaction(function () use ($subject, $currentPeriod, $dimension, $date, $transition): void {
            $currentPeriod->update([
                'ended_at' => $date,
            ]);

            $this->recordTransition($subject, $dimension, $transition, $date);
        });
    }

    /**
     * Run lifecycle persistence atomically without nesting an existing transaction.
     *
     * @param  Closure(): void  $operation
     */
    private function runWithinTransaction(Closure $operation): void
    {
        if (DB::transactionLevel() > 0) {
            $operation();

            return;
        }

        DB::transaction($operation);
    }

    private function recordTransition(
        Model $subject,
        LifecycleDimension $dimension,
        ?LifecycleTransitionType $transition,
        Carbon $date,
    ): void {
        if ($transition === null) {
            return;
        }

        $this->recordLifecycleTransition->handle($subject, $dimension, $transition, $date);
    }
}
