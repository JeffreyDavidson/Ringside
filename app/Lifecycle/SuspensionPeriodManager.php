<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Models\Contracts\Suspendable;
use Illuminate\Support\Carbon;

final class SuspensionPeriodManager
{
    /**
     * @param  Suspendable<*, *>  $suspendable
     */
    public function start(Suspendable $suspendable, Carbon $date): void
    {
        $suspendable->suspensions()->create([
            'started_at' => $date,
            'ended_at' => null,
        ]);
    }

    /**
     * @param  Suspendable<*, *>  $suspendable
     */
    public function end(Suspendable $suspendable, Carbon $date): void
    {
        $suspendable->currentSuspension()->update([
            'ended_at' => $date,
        ]);
    }
}
