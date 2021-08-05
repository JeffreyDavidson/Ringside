<?php

namespace App\Strategies\Injure;

use Carbon\Carbon;

interface InjuryStrategyInterface
{
    public function injure(Carbon $injuredAt = null);
}
