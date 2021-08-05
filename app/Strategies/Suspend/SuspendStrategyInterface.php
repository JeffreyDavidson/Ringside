<?php

namespace App\Strategies\Suspend;

use Carbon\Carbon;

interface SuspendStrategyInterface
{
    public function suspend(Carbon $suspendedAt = null);
}
