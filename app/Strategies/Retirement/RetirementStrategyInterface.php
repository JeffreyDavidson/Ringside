<?php

namespace App\Strategies\Retirement;

use Carbon\Carbon;

interface RetirementStrategyInterface
{
    public function retire(Carbon $retiredAt = null);
}
