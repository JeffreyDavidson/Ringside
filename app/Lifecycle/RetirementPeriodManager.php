<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class RetirementPeriodManager
{
    public function __construct(private RecordLifecycleTransitionAction $recordLifecycleTransition) {}

    /**
     * @param  Model&Retirable<*>  $retirable
     */
    public function start(
        Model&Retirable $retirable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        DB::transaction(function () use ($retirable, $date, $transition): void {
            $retirable->retirements()->create([
                'started_at' => $date,
                'ended_at' => null,
            ]);

            if ($transition !== null) {
                $this->recordLifecycleTransition->handle(
                    $retirable,
                    LifecycleDimension::Retirement,
                    $transition,
                    $date,
                );
            }
        });
    }

    /**
     * @param  Model&Retirable<*>  $retirable
     */
    public function end(
        Model&Retirable $retirable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        DB::transaction(function () use ($retirable, $date, $transition): void {
            $retirable->currentRetirement()->update([
                'ended_at' => $date,
            ]);

            if ($transition !== null) {
                $this->recordLifecycleTransition->handle(
                    $retirable,
                    LifecycleDimension::Retirement,
                    $transition,
                    $date,
                );
            }
        });
    }
}
