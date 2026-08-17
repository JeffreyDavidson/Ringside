<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class SuspensionPeriodManager
{
    public function __construct(private LifecyclePeriodWriter $periodWriter) {}

    /**
     * @param  Model&Suspendable<*>  $suspendable
     */
    public function start(
        Model&Suspendable $suspendable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        $this->periodWriter->start(
            $suspendable,
            $suspendable->suspensions(),
            LifecycleDimension::Suspension,
            $date,
            $transition,
        );
    }

    /**
     * @param  Model&Suspendable<*>  $suspendable
     */
    public function end(
        Model&Suspendable $suspendable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        $this->periodWriter->end(
            $suspendable,
            $suspendable->currentSuspension(),
            LifecycleDimension::Suspension,
            $date,
            $transition,
        );
    }
}
