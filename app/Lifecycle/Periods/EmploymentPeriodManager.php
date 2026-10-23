<?php

declare(strict_types=1);

namespace App\Lifecycle\Periods;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Employable;
use App\Models\Lifecycle\Employment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Every write here runs inside the calling Action's transaction and opens none of its own.
 */
final readonly class EmploymentPeriodManager
{
    public function __construct(
        private LifecyclePeriodWriter $periodWriter,
        private LifecycleTransitionRecorder $transitionRecorder,
    ) {}

    /**
     * @param  Model&Employable<*>  $employable
     */
    public function start(
        Model&Employable $employable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        $this->periodWriter->start(
            $employable,
            $employable->employments(),
            LifecycleDimension::Employment,
            $date,
            $transition,
        );
    }

    /**
     * @param  Model&Employable<*>  $employable
     */
    public function end(
        Model&Employable $employable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
        bool $clampToStart = false,
    ): void {
        $this->periodWriter->end(
            $employable,
            $employable->currentEmployment(),
            LifecycleDimension::Employment,
            $date,
            $transition,
            $clampToStart,
        );
    }

    /**
     * Move the not-yet-started employment to a new start date and record a new employed transition at it, the way a
     * title's scheduled debut moves. The earlier transition stays.
     *
     * @param  Model&Employable<*>  $employable
     *
     * @throws LogicException When the record has no scheduled employment that is its only one
     */
    public function reschedule(Model&Employable $employable, Carbon $date): void
    {
        $employment = $this->lockScheduled($employable);
        $scheduledFor = $employment->started_at;

        $employment->update(['started_at' => $date]);

        // Transitions are an immutable trail, so the move is recorded as a new one, as a title's reschedule is.
        $this->transitionRecorder->record(
            $employable,
            LifecycleDimension::Employment,
            LifecycleTransitionType::Employed,
            $date,
            ['rescheduled_from' => $scheduledFor->toIso8601String()],
        );
    }

    /**
     * Cancel the not-yet-started employment: the period is deleted and an employment-cancelled transition is
     * recorded. The earlier employed transition stays.
     *
     * @param  Model&Employable<*>  $employable
     *
     * @throws LogicException When the record has no scheduled employment that is its only one
     */
    public function cancel(Model&Employable $employable): void
    {
        $employment = $this->lockScheduled($employable);

        $scheduledFor = $employment->started_at;

        $employment->delete();
        $this->transitionRecorder->record(
            $employable,
            LifecycleDimension::Employment,
            LifecycleTransitionType::EmploymentCancelled,
            now(),
            ['scheduled_for' => $scheduledFor->toIso8601String()],
        );
    }

    /**
     * @param  Model&Employable<*>  $employable
     */
    private function lockScheduled(Model&Employable $employable): Employment
    {
        $employment = $employable->soleScheduledEmployment();

        if (! $employment instanceof Employment) {
            throw new LogicException(class_basename($employable).' has no scheduled employment to change.');
        }

        return Employment::query()->whereKey($employment->getKey())->lockForUpdate()->firstOrFail();
    }
}
