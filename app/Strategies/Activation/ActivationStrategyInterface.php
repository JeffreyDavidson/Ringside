<?php

namespace App\Strategies\Activation;

use Carbon\Carbon;

interface ActivationStrategyInterface
{
    public function activate(Carbon $startedAt = null);
}
