<?php

namespace App\Strategies\Employment;

use Carbon\Carbon;

interface EmploymentStrategyInterface
{
    public function employ(Carbon $startedAt = null);
}
