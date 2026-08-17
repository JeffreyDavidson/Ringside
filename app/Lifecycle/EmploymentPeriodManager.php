<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Employable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class EmploymentPeriodManager
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
    ): void {
        $this->periodWriter->end(
            $employable,
            $employable->currentEmployment(),
            LifecycleDimension::Employment,
            $date,
            $transition,
        );
    }
}
