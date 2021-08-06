<?php

namespace App\Strategies\Reinstate;

use Carbon\Carbon;

interface ReinstateStrategyInterface
{
    /**
     * Reinstate a reinstatable model.
     *
     * @param  \Carbon\Carbon|null $reinstatedAt
     * @return void
     */
    public function reinstate(Carbon $reinstatedAt = null);
}
