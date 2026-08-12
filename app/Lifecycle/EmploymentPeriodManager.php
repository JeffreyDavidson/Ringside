<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Employable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EmploymentPeriodManager
{
    public function __construct(private RecordLifecycleTransitionAction $recordLifecycleTransition) {}

    /**
     * @param  Model&Employable<*>  $employable
     */
    public function start(
        Model&Employable $employable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        DB::transaction(function () use ($employable, $date, $transition): void {
            $employable->employments()->create([
                'started_at' => $date,
                'ended_at' => null,
            ]);

            if ($transition !== null) {
                $this->recordLifecycleTransition->handle(
                    $employable,
                    LifecycleDimension::Employment,
                    $transition,
                    $date,
                );
            }
        });
    }

    /**
     * @param  Model&Employable<*>  $employable
     */
    public function end(
        Model&Employable $employable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        DB::transaction(function () use ($employable, $date, $transition): void {
            $employable->currentEmployment()->update([
                'ended_at' => $date,
            ]);

            if ($transition !== null) {
                $this->recordLifecycleTransition->handle(
                    $employable,
                    LifecycleDimension::Employment,
                    $transition,
                    $date,
                );
            }
        });
    }
}
