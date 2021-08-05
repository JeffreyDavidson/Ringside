<?php

namespace App\Strategies\ClearInjury;

use Carbon\Carbon;

interface ClearInjuryStrategyInterface
{
    public function clearInjury(Carbon $recoveredAt = null);
}
