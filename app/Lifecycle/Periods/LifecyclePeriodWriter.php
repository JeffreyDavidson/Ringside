<?php

declare(strict_types=1);

namespace App\Lifecycle\Periods;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
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
        DB::transaction(function () use ($subject, $periods, $dimension, $date, $transition): void {
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
        DB::transaction(function () use ($subject, $currentPeriod, $dimension, $date, $transition): void {
            $currentPeriod->update([
                'ended_at' => $date,
            ]);

            $this->recordTransition($subject, $dimension, $transition, $date);
        });
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
