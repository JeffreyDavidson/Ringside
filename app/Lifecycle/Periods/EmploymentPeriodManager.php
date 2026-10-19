<?php

declare(strict_types=1);

namespace App\Lifecycle\Periods;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Employable;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\LifecycleTransition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class EmploymentPeriodManager
{
    public function __construct(private LifecyclePeriodWriter $periodWriter) {}

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
     * Move the not-yet-started employment to a new start date, with its employed transition, the way a title's
     * scheduled debut moves.
     *
     * @param  Model&Employable<*>  $employable
     *
     * @throws LogicException When the record has no scheduled employment that is its only one
     */
    public function reschedule(Model&Employable $employable, Carbon $date): void
    {
        DB::transaction(function () use ($employable, $date): void {
            $employment = $this->lockScheduled($employable);
            $scheduledFor = $employment->started_at;

            $employment->update(['started_at' => $date]);
            $this->scheduledTransition($employable, $scheduledFor)->update(['effective_at' => $date]);
        });
    }

    /**
     * Cancel the not-yet-started employment: the period and its employed transition are removed, leaving the
     * record as if it had never been hired.
     *
     * @param  Model&Employable<*>  $employable
     *
     * @throws LogicException When the record has no scheduled employment that is its only one
     */
    public function cancel(Model&Employable $employable): void
    {
        DB::transaction(function () use ($employable): void {
            $employment = $this->lockScheduled($employable);

            $this->scheduledTransition($employable, $employment->started_at)->delete();
            $employment->delete();
        });
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

    /**
     * @param  Model&Employable<*>  $employable
     * @return Builder<LifecycleTransition>
     */
    private function scheduledTransition(Model&Employable $employable, Carbon $scheduledFor): Builder
    {
        return LifecycleTransition::query()
            ->where('subject_type', $employable->getMorphClass())
            ->where('subject_id', $employable->getKey())
            ->where('dimension', LifecycleDimension::Employment)
            ->where('transition', LifecycleTransitionType::Employed)
            ->where('effective_at', $scheduledFor);
    }
}
