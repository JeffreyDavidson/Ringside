<?php

namespace App\Strategies\Unretire;

use Carbon\Carbon;

interface UnretireStrategyInterface
{
    public function unretire(Carbon $unretiredAt = null);
}
