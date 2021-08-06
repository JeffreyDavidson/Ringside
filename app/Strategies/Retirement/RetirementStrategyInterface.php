<?php

namespace App\Strategies\Retirement;

use Carbon\Carbon;

interface RetirementStrategyInterface
{
    /**
     * Retire a retirable model.
     *
     * @param  \Carbon\Carbon|null $retiredAt
     * @return void
     */
    public function retire(Carbon $retiredAt = null);
}
