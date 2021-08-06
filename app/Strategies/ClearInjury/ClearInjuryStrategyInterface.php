<?php

namespace App\Strategies\ClearInjury;

use Carbon\Carbon;

interface ClearInjuryStrategyInterface
{
    /**
     * Clear an injury of an injurable model.
     *
     * @param  \Carbon\Carbon|null $recoveredAt
     * @return void
     */
    public function clearInjury(Carbon $recoveredAt = null);
}
