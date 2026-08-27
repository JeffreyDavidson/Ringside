<?php

declare(strict_types=1);

namespace App\Services\Titles;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;

final class TitleActivityPeriodService
{
    public function __construct(
        private readonly StartActivityPeriodAction $startActivityPeriod,
        private readonly EndActivityPeriodAction $endActivityPeriod,
        private readonly RecordLifecycleTransitionAction $recordLifecycleTransition,
    ) {}

    public function start(
        Title $title,
        Carbon $date,
        LifecycleTransitionType $transition,
        ?string $notes = null,
    ): void {
        $this->startActivityPeriod->handle($title, $date, rescheduleFuturePeriod: true);
        $this->recordLifecycleTransition->handle(
            $title,
            LifecycleDimension::Activity,
            $transition,
            $date,
            array_filter(['notes' => $notes]),
        );
    }

    public function end(Title $title, Carbon $date, LifecycleTransitionType $transition, ?string $notes = null): void
    {
        $this->endActivityPeriod->handle($title, $date);
        $this->recordLifecycleTransition->handle(
            $title,
            LifecycleDimension::Activity,
            $transition,
            $date,
            array_filter(['notes' => $notes]),
        );
    }
}
