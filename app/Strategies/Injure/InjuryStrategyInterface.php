<?php

namespace App\Strategies\Injure;

use Carbon\Carbon;

interface InjuryStrategyInterface
{
    /**
     * Injure an injurable model.
     *
     * @param  \Carbon\Carbon|null $injuredAt
     * @return void
     */
    public function injure(Carbon $injuredAt = null);
}
