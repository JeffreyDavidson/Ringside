<?php

namespace App\Strategies\Deactivation;

use Carbon\Carbon;

interface DeactivationStrategyInterface
{
    public function deactivate(Carbon $startedAt = null);
}
