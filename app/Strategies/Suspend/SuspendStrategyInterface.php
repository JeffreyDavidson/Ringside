<?php

namespace App\Strategies\Suspend;

use Carbon\Carbon;

interface SuspendStrategyInterface
{
    /**
     * Suspend a suspendable model.
     *
     * @param  \Carbon\Carbon|null $suspendedAt
     * @return void
     */
    public function suspend(Carbon $suspendedAt = null);
}
