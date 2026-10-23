<?php

declare(strict_types=1);

namespace App\Lifecycle\Periods;

use App\Enums\Lifecycle\DateRangeContext;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * Every write here runs inside the calling Action's transaction and opens none of its own.
 */
final readonly class LifecyclePeriodWriter
{
    public function __construct(private LifecycleTransitionRecorder $transitionRecorder) {}

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
        $periods->create([
            'started_at' => $date,
            'ended_at' => null,
        ]);

        $this->recordTransition($subject, $dimension, $transition, $date);
    }

    /**
     * End the open period on the given date.
     *
     * A date supplied by the caller for the period itself is validated: one before the period's start
     * throws. A period ended as a side effect of another transition (release or retire cascades, deletion)
     * passes $clampToStart so it ends on its own start date instead, which keeps the end from preceding
     * the start (open periods may start in the future).
     *
     * @param  MorphOne<*, *>  $currentPeriod
     *
     * @throws InvalidDateRangeException When the date precedes the period's start and $clampToStart is false
     */
    public function end(
        Model $subject,
        MorphOne $currentPeriod,
        LifecycleDimension $dimension,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
        bool $clampToStart = false,
    ): void {
        $startedAt = $currentPeriod->value('started_at');
        $endedAt = $date;

        if ($startedAt instanceof Carbon && $date->lt($startedAt)) {
            if (! $clampToStart) {
                throw InvalidDateRangeException::endBeforeStart($startedAt, $date, DateRangeContext::forPeriod($dimension));
            }

            $endedAt = $startedAt;
        }

        $currentPeriod->update([
            'ended_at' => $endedAt,
        ]);

        $this->recordTransition($subject, $dimension, $transition, $endedAt);
    }

    private function recordTransition(
        Model $subject,
        LifecycleDimension $dimension,
        ?LifecycleTransitionType $transition,
        Carbon $date,
    ): void {
        if (! $transition instanceof LifecycleTransitionType) {
            return;
        }

        $this->transitionRecorder->record($subject, $dimension, $transition, $date);
    }
}
