<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class SuspensionPeriodManager
{
    public function __construct(private RecordLifecycleTransitionAction $recordLifecycleTransition) {}

    /**
     * @param  Model&Suspendable<*>  $suspendable
     */
    public function start(
        Model&Suspendable $suspendable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        DB::transaction(function () use ($suspendable, $date, $transition): void {
            $suspendable->suspensions()->create([
                'started_at' => $date,
                'ended_at' => null,
            ]);

            if ($transition !== null) {
                $this->recordLifecycleTransition->handle(
                    $suspendable,
                    LifecycleDimension::Suspension,
                    $transition,
                    $date,
                );
            }
        });
    }

    /**
     * @param  Model&Suspendable<*>  $suspendable
     */
    public function end(
        Model&Suspendable $suspendable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        DB::transaction(function () use ($suspendable, $date, $transition): void {
            $suspendable->currentSuspension()->update([
                'ended_at' => $date,
            ]);

            if ($transition !== null) {
                $this->recordLifecycleTransition->handle(
                    $suspendable,
                    LifecycleDimension::Suspension,
                    $transition,
                    $date,
                );
            }
        });
    }
}
