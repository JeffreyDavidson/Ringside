<?php

declare(strict_types=1);

namespace App\Lifecycle\Periods;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Retirable;
use App\Models\Lifecycle\LifecycleTransition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final readonly class RetirementPeriodManager
{
    public function __construct(private LifecyclePeriodWriter $periodWriter) {}

    /**
     * @param  Model&Retirable<*>  $retirable
     */
    public function start(
        Model&Retirable $retirable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        $this->periodWriter->start(
            $retirable,
            $retirable->retirements(),
            LifecycleDimension::Retirement,
            $date,
            $transition,
        );
    }

    /**
     * @param  Model&Retirable<*>  $retirable
     */
    public function end(
        Model&Retirable $retirable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
        bool $clampToStart = false,
    ): void {
        $this->periodWriter->end(
            $retirable,
            $retirable->currentRetirement(),
            LifecycleDimension::Retirement,
            $date,
            $transition,
            $clampToStart,
        );
    }

    /**
     * Whether the subject was employed at the moment the current retirement began.
     *
     * Retiring ends an open employment on the retirement date without a Released transition, so an
     * employment that ended on or after that date was ended by the retirement. One that ended earlier,
     * or on the same date but through a release, means the subject was already released.
     *
     * @param  Model&Retirable<*>&Employable<*>  $subject
     */
    public function wasEmployedWhenRetired(Model&Retirable&Employable $subject): bool
    {
        $retiredAt = $subject->currentRetirement()->value('started_at');

        if (! $retiredAt instanceof Carbon) {
            return false;
        }

        return $subject->employments()
            ->where('ended_at', '>=', $retiredAt)
            ->whereNotExists(
                LifecycleTransition::query()
                    ->whereColumn('subject_id', 'employments.employable_id')
                    ->where('subject_type', $subject->getMorphClass())
                    ->where('dimension', LifecycleDimension::Employment)
                    ->where('transition', LifecycleTransitionType::Released)
                    ->whereColumn('effective_at', 'employments.ended_at'),
            )
            ->exists();
    }
}
