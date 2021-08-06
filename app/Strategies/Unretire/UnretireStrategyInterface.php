<?php

namespace App\Strategies\Unretire;

use Carbon\Carbon;

interface UnretireStrategyInterface
{
    /**
     * Unretire an unretirable model.
     *
     * @param  \Carbon\Carbon|null $unretiredAt
     * @return void
     */
    public function unretire(Carbon $unretiredAt = null);
}
