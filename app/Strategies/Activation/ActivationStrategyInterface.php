<?php

namespace App\Strategies\Activation;

use Carbon\Carbon;

interface ActivationStrategyInterface
{
    /**
     * Activate an activatable model.
     *
     * @param  \Carbon\Carbon|null $startedAt
     * @return void
     */
    public function activate(Carbon $startedAt = null);
}
