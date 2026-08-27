<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Carbon;

final class StableActivityPeriodService
{
    public function __construct(
        private readonly StartActivityPeriodAction $startActivityPeriod,
        private readonly EndActivityPeriodAction $endActivityPeriod,
        private readonly RecordLifecycleTransitionAction $recordLifecycleTransition,
    ) {}

    public function start(Stable $stable, Carbon $date, LifecycleTransitionType $transition, ?Carbon $endDate = null): ActivityPeriod
    {
        $activityPeriod = $this->startActivityPeriod->handle($stable, $date);

        if ($endDate !== null) {
            $activityPeriod->update(['ended_at' => $endDate]);
        }

        $this->recordLifecycleTransition->handle(
            $stable,
            LifecycleDimension::Activity,
            $transition,
            $date,
            array_filter(['ended_at' => $endDate?->toDateTimeString()]),
        );

        return $activityPeriod;
    }

    public function end(Stable $stable, Carbon $date, LifecycleTransitionType $transition): void
    {
        $this->endActivityPeriod->handle($stable, $date);
        $this->recordLifecycleTransition->handle($stable, LifecycleDimension::Activity, $transition, $date);
    }
}
