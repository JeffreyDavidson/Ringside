<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Models\Contracts\Retirable;
use Illuminate\Support\Carbon;

final class RetirementPeriodManager
{
    /**
     * @param  Retirable<*>  $retirable
     */
    public function start(Retirable $retirable, Carbon $date): void
    {
        $retirable->retirements()->create([
            'started_at' => $date,
            'ended_at' => null,
        ]);
    }

    /**
     * @param  Retirable<*>  $retirable
     */
    public function end(Retirable $retirable, Carbon $date): void
    {
        $retirable->currentRetirement()->update([
            'ended_at' => $date,
        ]);
    }
}
