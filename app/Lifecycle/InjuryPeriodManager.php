<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Injurable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class InjuryPeriodManager
{
    public function __construct(private LifecyclePeriodWriter $periodWriter) {}

    /**
     * @param  Model&Injurable<*>  $injurable
     */
    public function start(
        Model&Injurable $injurable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        $this->periodWriter->start(
            $injurable,
            $injurable->injuries(),
            LifecycleDimension::Injury,
            $date,
            $transition,
        );
    }

    /**
     * @param  Model&Injurable<*>  $injurable
     */
    public function end(
        Model&Injurable $injurable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        $this->periodWriter->end(
            $injurable,
            $injurable->currentInjury(),
            LifecycleDimension::Injury,
            $date,
            $transition,
        );
    }
}
