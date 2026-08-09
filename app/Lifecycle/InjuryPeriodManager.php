<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Models\Contracts\Injurable;
use Illuminate\Support\Carbon;

final class InjuryPeriodManager
{
    /**
     * Start an injury period.
     *
     * @param  Injurable<*, *>  $injurable
     */
    public function start(Injurable $injurable, Carbon $date): void
    {
        $injurable->injuries()->create([
            'started_at' => $date,
            'ended_at' => null,
        ]);
    }

    /**
     * End the active injury period.
     *
     * @param  Injurable<*, *>  $injurable
     */
    public function end(Injurable $injurable, Carbon $date): void
    {
        $injurable->currentInjury()->update([
            'ended_at' => $date,
        ]);
    }
}
