<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\Injurable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class InjuryPeriodManager
{
    public function __construct(private RecordLifecycleTransitionAction $recordLifecycleTransition) {}

    /**
     * @param  Model&Injurable<*>  $injurable
     */
    public function start(
        Model&Injurable $injurable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        DB::transaction(function () use ($injurable, $date, $transition): void {
            $injurable->injuries()->create([
                'started_at' => $date,
                'ended_at' => null,
            ]);

            if ($transition !== null) {
                $this->recordLifecycleTransition->handle(
                    $injurable,
                    LifecycleDimension::Injury,
                    $transition,
                    $date,
                );
            }
        });
    }

    /**
     * @param  Model&Injurable<*>  $injurable
     */
    public function end(
        Model&Injurable $injurable,
        Carbon $date,
        ?LifecycleTransitionType $transition = null,
    ): void {
        DB::transaction(function () use ($injurable, $date, $transition): void {
            $injurable->currentInjury()->update([
                'ended_at' => $date,
            ]);

            if ($transition !== null) {
                $this->recordLifecycleTransition->handle(
                    $injurable,
                    LifecycleDimension::Injury,
                    $transition,
                    $date,
                );
            }
        });
    }
}
