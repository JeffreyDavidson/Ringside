<?php

namespace App\Strategies\Reinstate;

use Carbon\Carbon;

interface ReinstateStrategyInterface
{
    public function reinstate(Carbon $reinstatedAt = null);
}
