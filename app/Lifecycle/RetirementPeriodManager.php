<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class RetirementPeriodManager
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
    ): void {
        $this->periodWriter->end(
            $retirable,
            $retirable->currentRetirement(),
            LifecycleDimension::Retirement,
            $date,
            $transition,
        );
    }
}
